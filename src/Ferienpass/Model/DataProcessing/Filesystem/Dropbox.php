<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


namespace Ferienpass\Model\DataProcessing\Filesystem;


use Dropbox\Exception_BadRequest;
use Dropbox\Exception_NetworkIO;
use Ferienpass\Flysystem\Plugin\DropboxDelta;
use Ferienpass\Model\DataProcessing;
use Ferienpass\Model\DataProcessing\FilesystemInterface;
use MetaModels\IItems;

class Dropbox implements FilesystemInterface
{
    /**
     * @var DataProcessing|\Model $model
     */
    private $model;

    /**
     * @var IItems $offers
     */
    private $offers;

    /**
     * {@inheritdoc}
     */
    public function __construct(DataProcessing $model, IItems $offers)
    {
        $this->model  = $model;
        $this->offers = $offers;
    }

    /**
     * @return DataProcessing|\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return IItems
     */
    public function getOffers()
    {
        return $this->offers;
    }

    /**
     * {@inheritdoc}
     */
    public function processFiles(array $files)
    {
        // Make sure to mount dropbox
        $mountManager = $this->getModel()->getMountManager('dropbox');

        foreach ($files as $file) {
            try {
                $path = str_replace($this->getModel()->getTmpPath() . '/', '', $file['path']);
                $mountManager->put(
                    'dropbox://' . $path,
                    $mountManager->read('local://' . $file['path'])
                );

            } catch (Exception_BadRequest $e) {
                // File was not uploaded
                // often because it is on the ignored file list
                \System::log(
                    sprintf('%s. Data processing ID %u', $e->getMessage(), $this->getModel()->id),
                    __METHOD__,
                    TL_GENERAL
                );
            } catch (Exception_NetworkIO $e) {
                // File was not uploaded
                // Connection refused
                \System::log(
                    sprintf('%s. Data processing ID %u', $e->getMessage(), $this->getModel()->id),
                    __METHOD__,
                    TL_ERROR
                );
            }
        }
    }

    /**
     * Sync from remote dropbox by fetching the delta (last edited files in dropbox)
     */
    public function syncFromRemoteDropbox()
    {
        if (!$this->getModel()->sync || 'dropbox' !== $this->getModel()->filesystem) {
            return;
        }

        $objMountManager = $this->getModel()->getMountManager('dropbox');

        $dbx = $objMountManager->getFilesystem('dropbox');
        $dbx->addPlugin(new DropboxDelta());

        /** @noinspection PhpUndefinedMethodInspection */
        $delta = $dbx->getDelta($this->getModel()->dropbox_cursor ?: null);

        // Save current cursor if "reset" is not set in response
        $this->getModel()->dropbox_cursor = !($this->getModel()->dropbox_cursor && $delta['reset'])
            ? $delta['cursor']
            : '';
        $this->getModel()->save();

        //log_message(var_export($arrDelta, true), 'syncFromRemoteDropbox.log');

        $files = [];

        // Walk each changed files in dropbox
        foreach ($delta['entries'] as $entry) {
            if ('dir' === $entry[1]['type']) {
                continue;
            }

            //@todo this is not nice
            $files[dirname($entry[0])][] = $entry;
        }

        // Process image folders
        foreach (
            [($this->getModel())::EXPORT_OFFER_IMAGES_PATH, ($this->getModel())::EXPORT_HOST_LOGOS_PATH] as
            $directory
        ) {
            // Files in directory was not changed
            if (!array_key_exists($directory, $files)) {
                continue;
            }

            $dbafs_path = $this->getLocalPathByRemotePath($directory);

            foreach ($files[$directory] as $entry) {
                // Remote file was deleted
                if (null === $entry[1]) {
                    if ($objMountManager->delete('dbafs://' . $dbafs_path . '/' . basename($entry[0]))) {
                        \System::log(
                            sprintf
                            (
                                'File "%s" was deleted by dropbox synchronisation. Data processing ID %u',
                                $dbafs_path . '/' . basename($entry[0]),
                                $this->id
                            ),
                            __METHOD__,
                            TL_GENERAL
                        );
                    } else {
                        \System::log(
                            sprintf
                            (
                                'File "%s" could not be deleted although it was deleted in the user\'s dropbox. Data processing ID %u',
                                $dbafs_path . '/' . basename($entry[0]),
                                $this->id
                            ),
                            __METHOD__,
                            TL_ERROR
                        );
                    }

                    continue;
                }

                $entry = $entry[1];

                if (!$objMountManager->has('dbafs://' . $dbafs_path . '/' . $entry['basename'])
                    || $objMountManager->getTimestamp('dropbox://' . $directory . '/' . $entry['basename'])
                       > $objMountManager->getTimestamp('dbafs://' . $dbafs_path . '/' . $entry['basename'])
                ) {
                    if ($objMountManager->put(
                        'dbafs://' . $dbafs_path . '/' . $entry['basename'],
                        $objMountManager->read('dropbox://' . $directory . '/' . $entry['basename'])
                    )
                    ) {
                        \System::log(
                            sprintf
                            (
                                'File "%s" was updated by dropbox synchronisation. Data processing ID %u',
                                $dbafs_path . '/' . $entry['basename'],
                                $this->id
                            ),
                            __METHOD__,
                            TL_GENERAL
                        );
                    } else {
                        \System::log(
                            sprintf
                            (
                                'File "%s" could not be updated although it was changed in the user\'s dropbox. Data processing ID %u',
                                $dbafs_path . '/' . $entry['basename'],
                                $this->id
                            ),
                            __METHOD__,
                            TL_ERROR
                        );
                    }
                }
            }
        }

        // Sync xml files
        if (array_key_exists(($this->getModel())::EXPORT_XML_FILES_PATH, $files)) {

            /** @var DataProcessing\Format\Xml $xmlHandler */
            if (($xmlHandler = $this->getModel()->getFormatHandler()) instanceof DataProcessing\Format\Xml) {

                # deleted xml files will not delete the offer
                $xmlHandler->syncXmlFilesWithModel(
                    array_map(
                        function ($value) {
                            return $value[1];
                        },
                        $files[($this->getModel())::EXPORT_XML_FILES_PATH]
                    ),
                    'dropbox'
                );
            }

        }
    }

    /**
     * Get the local path of a folder that has a different path on the remote system
     *
     * @param string $strPath
     *
     * @return string
     */
    protected function getLocalPathByRemotePath($strPath)
    {
        // Remove trailing slashes
        if ('/' === substr($strPath, -1)) {
            $strPath = substr($strPath, 0, -1);
        }

        switch ($strPath) {
            case ($this->getModel())::EXPORT_OFFER_IMAGES_PATH:
                return \FilesModel::findByPk($this->getModel()->offer_image_path)->path;
                break;

            case ($this->getModel())::EXPORT_HOST_LOGOS_PATH:
                return \FilesModel::findByPk($this->getModel()->host_logo_path)->path;
                break;

            default:
                return '';
                break;
        }
    }
}
