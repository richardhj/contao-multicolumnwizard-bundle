<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Model;

use Contao\Model;
use Dropbox\Client;
use Ferienpass\Flysystem\Plugin\DropboxDelta;
use Ferienpass\Model\DataProcessing\Filesystem\Dropbox;
use Ferienpass\Model\DataProcessing\Filesystem\Local;
use Ferienpass\Model\DataProcessing\Filesystem\SendToBrowser;
use Ferienpass\Model\DataProcessing\FilesystemInterface;
use Ferienpass\Model\DataProcessing\FormatInterface;
use League\Flysystem\Dropbox\DropboxAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use MetaModels\Attribute\IAttribute;
use MetaModels\IItems;


/**
 * @property string  $name
 * @property integer $metamodel_view
 * @property string  $scope
 * @property string  $filesystem
 * @property mixed   $offer_image_path
 * @property mixed   $host_logo_path
 * @property string  $export_file_name
 * @property string  $dropbox_access_token
 * @property string  $dropbox_uid
 * @property string  $dropbox_cursor
 * @property string  $path_prefix
 * @property boolean $sync
 * @property mixed   $ical_fields
 * @property string  $type
 * @property boolean $combine_variants
 */
class DataProcessing extends Model
{

    /**
     * Folder paths for export or rather on remote systems
     */
    const EXPORT_OFFER_IMAGES_PATH = 'offer_images';


    const EXPORT_HOST_LOGOS_PATH = 'host_logos';


    const EXPORT_XML_FILES_PATH = 'xml';


    const VARIANT_DELIMITER = ', ';


    /**
     * Table name
     *
     * @var string
     */
    protected static $strTable = 'tl_ferienpass_dataprocessing';


    /**
     * @var FormatInterface
     */
    private $formatHandler;


    /**
     * @var FilesystemInterface
     */
    private $fileSystemHandler;


    /**
     * @var IItems
     */
    private $offers;


    /**
     * @var string
     */
    private $tmpPath;


    /**
     * Get a offer image's relative path for a xml file on a remote system
     *
     * @param string $strFileName The file name
     *
     * @return string
     */
    public static function getRelativeOfferImagePathOnRemoteSystem($strFileName)
    {
        return 'file://../'.static::EXPORT_OFFER_IMAGES_PATH.'/'.$strFileName;
    }


    /**
     * Get a host logo's relative path for a xml file on a remote system
     *
     * @param string $strFileName The file name
     *
     * @return string
     */
    public static function getRelativeHostLogoPathOnRemoteSystem($strFileName)
    {
        return 'file://../'.static::EXPORT_HOST_LOGOS_PATH.'/'.$strFileName;
    }


    /**
     * Sync from remote dropbox by fetching the delta (last edited files in dropbox)
     *
     * @return bool True if synchronization was processed
     */
    public function syncFromRemoteDropbox()
    {
        if (!$this->sync || $this->filesystem != 'dropbox') {
            return false;
        }

        $objMountManager = $this->getMountManager('dropbox');

        $dbx = $objMountManager->getFilesystem('dropbox');
        $dbx->addPlugin(new DropboxDelta());

        /** @noinspection PhpUndefinedMethodInspection */
        $arrDelta = $dbx->getDelta($this->dropbox_cursor ?: null);

        // Save current cursor if "reset" is not set in response
        $this->dropbox_cursor = !($this->dropbox_cursor && $arrDelta['reset']) ? $arrDelta['cursor'] : '';
        $this->save();

        //log_message(var_export($arrDelta, true), 'syncFromRemoteDropbox.log');

        $arrFiles = [];

        // Walk each changed files in dropbox
        foreach ($arrDelta['entries'] as $entry) {
            if ('dir' === $entry[1]['type']) {
                continue;
            }

            //@todo this is not nice
            $arrFiles[dirname($entry[0])][] = $entry;
        }

        // Process image folders
        foreach ([self::EXPORT_OFFER_IMAGES_PATH, self::EXPORT_HOST_LOGOS_PATH] as $directory) {
            // Files in directory was not changed
            if (!array_key_exists($directory, $arrFiles)) {
                continue;
            }

            $dbafs_path = $this->getLocalPathByRemotePath($directory);

            foreach ($arrFiles[$directory] as $entry) {
                // Remote file was deleted
                if (null === $entry[1]) {
                    if ($objMountManager->delete('dbafs://'.$dbafs_path.'/'.basename($entry[0]))) {
                        \System::log(
                            sprintf
                            (
                                'File "%s" was deleted by dropbox synchronisation. Data processing ID %u',
                                $dbafs_path.'/'.basename($entry[0]),
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
                                $dbafs_path.'/'.basename($entry[0]),
                                $this->id
                            ),
                            __METHOD__,
                            TL_ERROR
                        );
                    }

                    continue;
                }

                $entry = $entry[1];

                if (!$objMountManager->has('dbafs://'.$dbafs_path.'/'.$entry['basename']) ||
                    $objMountManager->getTimestamp('dropbox://'.$directory.'/'.$entry['basename'])
                    > $objMountManager->getTimestamp('dbafs://'.$dbafs_path.'/'.$entry['basename'])
                ) {
                    if ($objMountManager->put(
                        'dbafs://'.$dbafs_path.'/'.$entry['basename'],
                        $objMountManager->read('dropbox://'.$directory.'/'.$entry['basename'])
                    )
                    ) {
                        \System::log(
                            sprintf
                            (
                                'File "%s" was updated by dropbox synchronisation. Data processing ID %u',
                                $dbafs_path.'/'.$entry['basename'],
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
                                $dbafs_path.'/'.$entry['basename'],
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
        if (array_key_exists(self::EXPORT_XML_FILES_PATH, $arrFiles)) {
            # deleted xml files will not delete the offer
            $this->syncXmlFilesWithModel(
                array_map(
                    function ($value) {
                        return $value[1];
                    },
                    $arrFiles[self::EXPORT_XML_FILES_PATH]
                ),
                'dropbox'
            );
        }

        return true;
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
        if (substr($strPath, -1) == '/') {
            $strPath = substr($strPath, 0, -1);
        }

        switch ($strPath) {
            case self::EXPORT_OFFER_IMAGES_PATH:
                return \FilesModel::findByPk($this->offer_image_path)->path;
                break;

            case self::EXPORT_HOST_LOGOS_PATH:
                return \FilesModel::findByPk($this->host_logo_path)->path;
                break;

            default:
                return '';
                break;
        }
    }


    /**
     * Synchronize given xml files with the MetaModel
     *
     * @param array  $arrFiles   The xml files. An array formatted like Filesystem->listContents() does
     * @param string $filesystem The filesystem the xml files come from
     */
    protected function syncXmlFilesWithModel($arrFiles, $filesystem = 'local')
    {
        /** @var MountManager $manager */
        $manager = $this->getMountManager($filesystem);

        // Skip if no files are handed over
        if (empty($arrFiles)) {
            return;
        }

        foreach ($arrFiles as $file) {
            // Only process xml files
            if ($file['mimetype'] != 'application/xml') {
                continue;
            }

            $blnChange = false;

            // Load xml document
            $objDom = new \DOMDocument('1.0', 'utf-8');
            $objDom->loadXML($manager->read($filesystem.'://'.$file['path']));

            /** @type \DOMElement $objRoot */
            $objRoot = $objDom->getElementsByTagName('Offer')->item(0);

            $objOffer = Offer::getInstance()->findById($objRoot->getAttribute('id'));
            $objVariants = null;

            // Fetch possible variants
            if ($this->combine_variants) {
                if ($objRoot->hasAttribute('variant_ids')) {
                    $objVariants = Offer::getInstance()->findMultipleByIds(
                        trimsplit(',', $objRoot->getAttribute('variant_ids'))
                    );
                }
            }

            // Existing offer was edited
            if ($file['timestamp'] < $objOffer->get('tstamp')) {
                // Override foreign xml from database
                $manager->put(
                    $filesystem.'://'.$file['path'],
                    $this->generateOfferXml($objOffer->get('id'))
                );

                \System::log(
                    sprintf(
                        'Could not sync XML file "%s" because offer ID %u was edited afterwards',
                        $file['path'],
                        $objOffer->get('id')
                    ),
                    __METHOD__,
                    TL_ERROR
                );

                continue;
            }

            /** @var \DOMElement $element */
            foreach ($objRoot->getElementsByTagName('*') as $element) {
                // Child nodes are passed too
                // We only want nodes parsed by an attribute here
                if (!$element->hasAttribute('attr_id')) {
                    continue;
                }

                $objAttribute = $objOffer->getMetaModel()->getAttributeById((int)$element->getAttribute('attr_id'));

                if (null === $objAttribute) {
                    continue;
                }

                // Attribute is variant attribute
                if ($this->combine_variants && $objVariants !== null && $objAttribute->get('isvariant')) {
                    $arrVariantValues = trimsplit(static::VARIANT_DELIMITER, $element->nodeValue);

                    if ($objVariants->getCount() != count($arrVariantValues)) {
                        \System::log(
                            sprintf
                            (
                                'Cannot import attribute "%s" (type "%s") for offer ID %u as the delimited variant values are not assignable. Variant IDs by xml attribute: %s. Resolved variant values: %s. Data processing ID %u',
                                $objAttribute->getColName(),
                                $objAttribute->get('type'),
                                $objOffer->get('id'),
                                $element->nodeValue,
                                var_export(trimsplit(',', $objRoot->getAttribute('variant_ids')), true),
                                var_export($arrVariantValues, true),
                                $this->id
                            ),
                            __METHOD__,
                            TL_ERROR
                        );

                        continue;
                    }

                    $objVariants->reset();

                    foreach ($objVariants as $i => $objVariant) {
                        $blnChangeVariant = false;

                        // Check for a proper variant
                        if ($objVariant->get('vargroup') != $objOffer->get('id')) {
                            \System::log(
                                sprintf
                                (
                                    'Offer ID %u is not a proper variant of offer ID %u. Rough changes between database and xml file make the processing unable to synchronize attribute ID %u. Data processing ID %u',
                                    $objVariant->get('id'),
                                    $objOffer->get('id'),
                                    $objAttribute->get('id'),
                                    $this->id
                                ),
                                __METHOD__,
                                TL_ERROR
                            );

                            continue;
                        }

                        $parsed = $objVariant->parseAttribute(
                            $objAttribute->getColName(),
                            'text',
                            $objVariant->getMetaModel()->getView($this->metamodel_view)
                        );

                        // Check for change
                        if ($arrVariantValues[$i] != $parsed['text']) {
                            //@todo whats with date?
                            $blnChangeVariant = true;

                            $objVariant->set
                            (
                                $objAttribute->getColName(),
                                $objAttribute->widgetToValue($arrVariantValues[$i], $objVariant->get('id'))
                            );
                        }

                        if ($blnChangeVariant) {
                            $objVariant->save();

                            \System::log(
                                sprintf
                                (
                                    'Attribute "%s" for offer variant ID %u was synced from xml file "%s". Data processing ID %u',
                                    $objAttribute->getColName(),
                                    $objVariant->get('id'),
                                    $file['path'],
                                    $this->id
                                ),
                                __METHOD__,
                                TL_GENERAL
                            );
                        }
                    }
                } // Attribute is not a variant attribute
                else {
                    // $widget will contain the data in the same format as attribute's 'raw' data will
                    try {
                        $widget = $this->domElementToNativeWidget(
                            $element,
                            $objAttribute,
                            $objOffer->get('id')
                        );
                    } catch (\RuntimeException $e) {
                        \System::log(
                            sprintf
                            (
                                'Could not sync XML file "%s" for offer ID %u. Error message: %s. Data processing ID %u',
                                $file['path'],
                                $objOffer->get('id'),
                                $e->getMessage(),
                                $this->id
                            ),
                            __METHOD__,
                            TL_ERROR
                        );

                        continue;
                    }

                    $parsed = $objOffer->parseAttribute(
                        $objAttribute->getColName(),
                        'text',
                        $objOffer->getMetaModel()->getView($this->metamodel_view)
                    );

                    // Widget can not be converted back because of its attribute type
                    if (null === $widget) {
                        // Do an approximative check
                        $objTestDom = new \DOMDocument('1.0', 'utf-8');
                        $objTestElement = $objTestDom->createElement($element->nodeName, $element->nodeValue);
                        $this->importXmlToNode($element->nodeValue, $objTestElement);

                        if ($element->nodeValue != $objTestElement->nodeValue) {
                            \System::log(
                                sprintf
                                (
                                    'Attribute "%s" (type "%s") for offer ID %u can not be updated although it was changed. XML value: "%s". Xml parsed database value: "%s". Raw database value: "%s". Data processing ID %u',
                                    $objAttribute->getColName(),
                                    $objAttribute->get('type'),
                                    $objOffer->get('id'),
                                    $element->nodeValue,
                                    $objTestElement->nodeValue,
                                    var_export($parsed['raw'], true),
                                    $this->id
                                ),
                                __METHOD__,
                                TL_ERROR
                            );
                        }
                    } // Check for change
                    elseif ($widget != $parsed['raw']) {
                        $blnChange = true;

                        $objOffer->set
                        (
                            $objAttribute->getColName(),
                            $widget
                        );
                    }
                }
            }

            if ($blnChange) {
                $objOffer->save();

                \System::log(
                    sprintf
                    (
                        'Offer ID %u was synced from xml file "%s". Data processing ID %u',
                        $objOffer->get('id'),
                        $file['path'],
                        $this->id
                    ),
                    __METHOD__,
                    TL_GENERAL
                );

                // Trigger sync for other linked dropboxes
                /** @var DataProcessing|\Model\Collection $objProcessings */
                $objProcessings = static::findBy
                (
                    [
                        'filesystem=?',
                        'sync=1',
                        'id<>?',
                    ],
                    [
                        'dropbox',
                        $this->id,
                    ]
                );

                while (null !== $objProcessings && $objProcessings->next()) {
                    $objProcessings->current()->run(
                        array_merge
                        (
                            [$objOffer->get('id')],
                            array_map(
                                function ($variant) {
                                    /** @noinspection PhpUndefinedMethodInspection */
                                    return $variant->get('id');
                                },
                                $objVariants
                            ) ?: []
                        )
                    );
                }
            }
        }
    }


    /**
     * Try to convert the DOMElement's content to a widget's raw data by the widget type
     *
     * @param \DOMElement $element
     * @param IAttribute  $objAttribute
     * @param integer     $intItemId
     *
     * @return mixed|null The attribute's data in the same format as the attribute's "raw" data
     */
    protected function domElementToNativeWidget($element, $objAttribute, $intItemId)
    {
        switch ($objAttribute->get('type')) {
            case 'alias':
            case 'combinedvalues':
            case 'decimal':
            case 'longtext':
            case 'numeric':
            case 'text':
            case 'url':
                // These attributes can easily adopted
                $widget = $element->nodeValue;
                break;

            case 'file':
                $widget = [];

                /** @type \DOMElement $file */
                foreach ($element->getElementsByTagName('Link') as $file) {
                    // Replace remote path with local path
                    $strPath = preg_replace_callback(
                        '/^file:\/\/.*?('.implode(
                            '|',
                            [self::EXPORT_OFFER_IMAGES_PATH, self::EXPORT_HOST_LOGOS_PATH]
                        ).')/',
                        function ($matches) {
                            return $this->getLocalPathByRemotePath($matches[1]);
                        },
                        $file->getAttribute('href')
                    );

                    $objFile = \FilesModel::findByPath(urldecode($strPath));

                    // Local file does not exist therefore the remote file was not uploaded
                    if (null === $objFile) {
                        throw new \RuntimeException(
                            sprintf
                            (
                                'File "%s" does not exist on local system. Sync files beforehand.',
                                $strPath
                            )
                        );
                    }

                    $widget[] = $objFile->uuid;
                }
                break;

            case 'tabletext':
                $widget = [];

                /** @type \DOMElement $element */
                $element = $element->getElementsByTagName('Tabletext')->item(0);

                $cc = $element->getAttribute('aid:tcols');
                $r = 0;
                $c = 0;

                /** @type \DOMElement $cell */
                foreach ($element->getElementsByTagName('Cell') as $cell) {
                    if ($c == $cc) {
                        $c = 0;
                        $r++;
                    }

                    $widget[$r]['col_'.$c] = $cell->nodeValue;

                    $c++;
                }
                break;

            case 'timestamp':
                //@todo import timestamp depended on render format
            default:
                // The attribute type is not supported to convert back
                return null;
                break;
        }

        // Convert the widget value to native MetaModel data
        return $objAttribute->widgetToValue($widget, $intItemId);
    }


    /**
     * Run data processing by its configuration
     *
     * @param array|string $arrOffers The offer to export
     *
     * @throws \Exception
     */
    public function run($arrOffers = [])
    {
        if (!empty($arrOffers)) {
            $this->scope = 'single';
        }

        switch ($this->scope) {
            case 'full':
                $this->offers = Offer::getInstance()->findAll();
                break;

            case 'single':
                $this->offers = (empty($arrOffers))
                    ? Offer::getInstance()->findAll()
                    : Offer::getInstance()->findMultipleByIds((array)$arrOffers);
                break;

        }

        $files = $this
                ->getFormatHandler()
                ->processOffers()
                ->getFiles();


//                throw new \LogicException(
//                    sprintf('Type "%s" is not implemented. Data processing ID %u', $this->type, $this->id)
//                );

        $this
            ->getFileSystemHandler()
            ->processFiles($files);

//                throw new \LogicException(
//                    sprintf('Filesystem "%s" is not implemented. Data processing ID %u', $this->filesystem, $this->id)
//                );

        // Delete xml tmp path
        $this->getMountManager()->deleteDir('local://'.$this->getTmpPath());
    }


    /**
     * @param array|string $varFilesystems The filesystem(s) you want to be mounted
     *
     * @return MountManager
     */
    public function getMountManager($varFilesystems = null)
    {
        if (null !== $varFilesystems) {
            foreach ((array)$varFilesystems as $filesystem) {
                $this->mountFileSystem($filesystem);
            }

        }

        return $GLOBALS['container']['flysystem.mount-manager'];
    }


    /**
     * @param string $strType
     *
     * @return bool
     */
    protected function mountFileSystem($strType)
    {
        switch ($strType) {
            case 'local':
            case 'dbafs':
                return true;
                break;

            case 'dropbox':
                # a legacy filesystem might be existent from an older model -> problems with processing collections -> make sure to bind the current user's dropbox
                $client = new Client($this->dropbox_access_token, \Config::get('dropbox_ferienpass_appSecret'));
                $adapter = new DropboxAdapter($client, $this->path_prefix ?: null);

                $this->getMountManager()->mountFilesystem($strType, new Filesystem($adapter));

                return true;
                break;

            default:
                return false;
                break;
        }
    }



    /**
     * @return FormatInterface
     */
    public function getFormatHandler()
    {
        return $this->formatHandler;
    }

    /**
     * @return FilesystemInterface
     */
    public function getFileSystemHandler()
    {
        if (null === $this->fileSystemHandler) {
            switch ($this->filesystem) {
                case 'local':
                    $this->fileSystemHandler = new Local($this, $this->getOffers());
                    break;

                case 'sendToBrowser':
                    $this->fileSystemHandler = new SendToBrowser($this, $this->getOffers());
                    break;

                case 'dropbox':
                    $this->fileSystemHandler = new Dropbox($this, $this->getOffers());
                    break;
            }
        }

        return $this->fileSystemHandler;
    }

    /**
     * @return string
     */
    public function getTmpPath()
    {
        if (null === $this->tmpPath)
        {
            $this->tmpPath  = 'system/tmp/'.time();
        }

        return $this->tmpPath;
    }

    /**
     * @return IItems
     */
    public function getOffers()
    {
        return $this->offers;
    }


    /**
     * @param string $strType
     *
     * @return \League\Flysystem\FilesystemInterface
     */
    protected function getFileSystem($strType)
    {
        return $this->getMountManager($strType)->getFilesystem($strType);
    }
}
