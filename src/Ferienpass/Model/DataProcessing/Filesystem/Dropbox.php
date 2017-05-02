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
     * @var IItems $items
     */
    private $items;

    /**
     * {@inheritdoc}
     */
    public function __construct(DataProcessing $model, IItems $items)
    {
        $this->model = $model;
        $this->items = $items;
    }

    /**
     * @return DataProcessing|\Model
     */
    public function getModel(): DataProcessing
    {
        return $this->model;
    }

    /**
     * @return IItems
     */
    public function getItems(): IItems
    {
        return $this->items;
    }

    /**
     * {@inheritdoc}
     */
    public function processFiles(array $files): void
    {
        // Make sure to mount dropbox
        $mountManager = $this->getModel()->getMountManager('dropbox');

        foreach ($files as $file) {
            try {
                // Trim tmp path from path
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
    public function syncFromRemoteDropbox(): void
    {
        if (!$this->getModel()->sync) {
            return;
        }

        $mountManager = $this
            ->getModel()
            ->getMountManager('dropbox');

        $dbx = $mountManager->getFilesystem('dropbox');
        $dbx->addPlugin(new DropboxDelta());

        /** @noinspection PhpUndefinedMethodInspection */
        $delta = $dbx->getDelta($this->getModel()->dropbox_cursor ?: null);

        // Save current cursor if "reset" is not set in response
        $this->getModel()->dropbox_cursor =
            !($this->getModel()->dropbox_cursor && $delta['reset'])
                ? $delta['cursor']
                : '';
        $this->getModel()->save();

        $files = $delta['entries'];

        // Process image folders
        foreach (deserialize($this->getModel()->static_dirs, true) as $dirBin) {
            $dir = (\FilesModel::findByPk($dirBin))->path;

            foreach ($files as $entry) {
                if ($dir !== $files['directory']) {
                    continue;
                }

                // Remote file was deleted
                if (null === $entry[1]) {
                    if ($mountManager->delete('dbafs://' . $dir . '/' . basename($entry[0]))) {
                        \System::log(
                            sprintf(
                                'File "%s" was deleted by dropbox synchronisation. Data processing ID %u',
                                $dir . '/' . basename($entry[0]),
                                $this->getModel()->id
                            ),
                            __METHOD__,
                            TL_GENERAL
                        );

                        continue;
                    }

                    $entry = $entry[1];

                    if (!$mountManager->has('dbafs://' . $entry['path'])
                        || $mountManager->getTimestamp('dropbox://' . $entry['path'])
                           > $mountManager->getTimestamp('dbafs://' . $entry['path'])
                    ) {
                        if ($mountManager->put(
                            'dbafs://' . $entry['path'],
                            $mountManager->read('dropbox://' . $entry['path'])
                        )
                        ) {
                            \System::log(
                                sprintf(
                                    'File "%s" was updated by dropbox synchronisation. Data processing ID %u',
                                    $entry['path'],
                                    $this->getModel()->id
                                ),
                                __METHOD__,
                                TL_GENERAL
                            );
                        } else {
                            \System::log(
                                sprintf(
                                    'File "%s" could not be updated although it was changed in the user\'s dropbox. Data processing ID %u',
                                    $entry['path'],
                                    $this->getModel()->id
                                ),
                                __METHOD__,
                                TL_ERROR
                            );
                        }
                    }
                }
            }
        }

        $formatHandler = $this->getModel()->getFormatHandler();
        $formatHandler->backSyncFiles(
            array_map(
                function ($value) {
                    return $value[1];
                },
                array_filter(
                    $files,
                    function ($value) {
                        return 'xml' === $value['qqqq'];
                    }
                )
            ),
            'dropbox'
        );
    }
}
