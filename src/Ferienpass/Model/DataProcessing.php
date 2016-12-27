<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Model;

use Contao\Model;
use Dropbox\Client;
use Dropbox\Exception_BadRequest;
use Dropbox\Exception_NetworkIO;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use Haste\DateTime\DateTime;
use League\Flysystem\Dropbox\DropboxAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use League\Flysystem\Plugin\DropboxDelta;
use MetaModels\Attribute\IAttribute;


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

        log_message(var_export($arrDelta, true), 'syncFromRemoteDropbox.log');

        $arrFiles = [];

        // Walk each changed files in dropbox
        foreach ($arrDelta['entries'] as $entry) {
            if ($entry[1]['type'] == 'dir') {
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
        $arrToExport = [];

        $objMountManger = $this->getMountManager();
        $objOffers = null;
        $strTmpPath = 'system/tmp/'.time();

        if (!empty($arrOffers)) {
            $this->scope = 'single';
        }

        switch ($this->scope) {
            case 'full':
                $objOffers = Offer::getInstance()->findAll();
                break;

            case 'single':
                $objOffers = (empty($arrOffers))
                    ? Offer::getInstance()->findAll()
                    : Offer::getInstance()->findMultipleByIds((array)$arrOffers);
                break;

        }

        switch ($this->type) {
            case 'xml':
                switch ($this->scope) {
                    case 'full':
                        // Fetch files from image folders
                        if (null !== ($objOfferImages = \FilesModel::findByPk($this->offer_image_path))) {
                            $arrToExport[self::EXPORT_OFFER_IMAGES_PATH] = $objMountManger->listContents(
                                'dbafs://'.$objOfferImages->path
                            );
                        }
                        if (null !== ($objHostLogos = \FilesModel::findByPk($this->host_logo_path))) {
                            $arrToExport[self::EXPORT_HOST_LOGOS_PATH] = $objMountManger->listContents(
                                'dbafs://'.$objHostLogos->path
                            );
                        }
                        break;

                    case 'single':
                        break;
                }

                if (null !== $objOffers) {
                    // Walk each offer
                    while ($objOffers->next()) {
                        $strXml = $this->generateOfferXml($objOffers->getItem()->get('id'));

                        if (false !== $strXml) {
                            $directory = $strTmpPath.'/offer_'.$objOffers->getItem()->get('id').'.xml';
                            $objMountManger->put('local://'.$directory, $strXml);

                            $arrToExport[self::EXPORT_XML_FILES_PATH] = $objMountManger->listContents(
                                'local://'.$strTmpPath
                            );
                        }
                    }
                }
                break;

            case 'ical':
                $path = $strTmpPath.'/'.$this->export_file_name.'.ics';
                $objMountManger->put('local://'.$path, $this->createICalForOffers($objOffers));
                $arrToExport[] = array_merge(
                    $objMountManger->getMetadata('local://'.$path),
                    ['basename' => basename($path)]
                );
                break;

            default:
                throw new \LogicException(
                    sprintf('Type "%s" is not implemented. Data processing ID %u', $this->type, $this->id)
                );
                break;
        }

        switch ($this->filesystem) {
            // Save files local
            case 'local':
                $path_prefix = ($this->path_prefix) ? $this->path_prefix.'/' : '';

                if (array_is_assoc($arrToExport)) {
                    foreach ($arrToExport as $directory => $arrFiles) {
                        foreach ($arrFiles as $file) {
                            $objMountManger->put(
                                'local://share/'.$path_prefix.$directory.'/'.$file['basename'],
                                $objMountManger->read('local://'.$file['path'])
                            );
                        }
                    }
                } else {
                    foreach ($arrToExport as $file) {
                        $objMountManger->put(
                            'local://share/'.$path_prefix.$file['basename'],
                            $objMountManger->read('local://'.$file['path'])
                        );
                    }
                }
                break;

            // Send zip file to browser
            case 'sendToBrowser':
                // Generate a zip file
                $objZip = new \ZipWriter($strTmpPath.'/export.zip');

                if (array_is_assoc($arrToExport)) {
                    foreach ($arrToExport as $directory => $arrFiles) {
                        foreach ($arrFiles as $file) {
                            $objZip->addFile($file['path'], $directory.'/'.$file['basename']);
                        }
                    }
                } else {
                    foreach ($arrToExport as $file) {
                        $objZip->addFile($file['path'], $file['basename']);
                    }
                }

                $objZip->close();

                // Output ZIP
                header('Content-type: application/octetstream');
                header('Content-Disposition: attachment; filename="'.$this->export_file_name.'.zip"');
                readfile(TL_ROOT.'/'.$strTmpPath.'/export.zip');
                exit;

                break;

            // Upload to user's dropbox
            case 'dropbox':

                // Make sure to mount dropbox
                $objMountManger = $this->getMountManager('dropbox');

                foreach ($arrToExport as $directory => $arrFiles) {
                    foreach ($arrFiles as $file) {
                        try {
                            $objMountManger->put(
                                'dropbox://'.$directory.'/'.$file['basename'],
                                $objMountManger->read('local://'.$file['path'])
                            );

                        } catch (Exception_BadRequest $e) {
                            // File was not uploaded
                            // often because it is on the ignored file list
                            \System::log(
                                sprintf('%s. Data processing ID %u', $e->getMessage(), $this->id),
                                __METHOD__,
                                TL_GENERAL
                            );
                        } catch (Exception_NetworkIO $e) {
                            // File was not uploaded
                            // Connection refused
                            \System::log(
                                sprintf('%s. Data processing ID %u', $e->getMessage(), $this->id),
                                __METHOD__,
                                TL_ERROR
                            );
                        }
                    }
                }
                break;

            default:
                throw new \LogicException(
                    sprintf('Filesystem "%s" is not implemented. Data processing ID %u', $this->filesystem, $this->id)
                );
                break;
        }

        // Delete xml tmp path
        $objMountManger->deleteDir('local://'.$strTmpPath);
    }


    /**
     * @param array|string $varFilesystems The filesystem(s) you want to be mounted
     *
     * @return MountManager
     */
    protected function getMountManager($varFilesystems = null)
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
     * Return the offer's xml as string
     *
     * @param integer $intId The offer's id
     *
     * @return string|false
     */
    public function generateOfferXml($intId)
    {
        $objOffer = Offer::getInstance()->findById($intId);
        $objVariants = null;

        // If we combine variants, only variant bases will be exported
        if ($this->combine_variants) {
            if ($objOffer->isVariant()) {
                return false;
            }

            $objVariants = $objOffer->getVariants(null);
        }

        $objRenderSetting = $objOffer->getMetaModel()->getView((int)$this->metamodel_view);

        // Create DOM
        $objDom = new \DOMDocument('1.0', 'utf-8');

        // Create comment
        $objCommentTemplate = new \FrontendTemplate('dataprocessing_xml_comment');
        $objCommentTemplate->setData($this->arrData);
        $objDom->appendChild($objDom->createComment($objCommentTemplate->parse()));

        $objRoot = $objDom->createElement('Offer');
        $objRoot->setAttribute('id', $intId);

        // Add variant ids (order will be important for following processings)
        if (null !== $objVariants && $objVariants->getCount()) {
            $arrVariantIds = [];

            while ($objVariants->next()) {
                $arrVariantIds[] = $objVariants->getItem()->get('id');
            }

            $objRoot->setAttribute('variant_ids', implode(',', $arrVariantIds));
        }

        $objDom->appendChild($objRoot);

        // Walk each attribute in render setting
        foreach ($objRenderSetting->getSettingNames() as $colName) {
            $objAttribute = $objOffer->getAttribute($colName);

            // It is a variant attribute
            if ($this->combine_variants && $objVariants->getCount() && $objAttribute->get('isvariant')) {
                // Fetch variants
                $parsed = [];
                $objVariants->reset();

                // Parse each attribute with render setting
                while ($objVariants->next()) {
                    $parsed[] = $objVariants->getItem()->parseAttribute($colName, 'text', $objRenderSetting)['text'];
                }

                // Combine variant attributes
                $parsed = implode(self::VARIANT_DELIMITER, $parsed);
            } // Default procedure for non-variant attributes
            else {
                // Parse attribute with render setting
                $parsed = $objOffer->parseAttribute($colName, 'text', $objRenderSetting);
                $parsed = $parsed['text'];
            }

            // Prepare attribute node by setting attribute id
            $attribute = $objDom->createElement(static::camelCaseColName($colName));
            $attribute->setAttribute('attr_id', $objAttribute->get('id'));

            // Set the attribute node's value
            $attribute->nodeValue = htmlspecialchars(
                html_entity_decode($parsed),
                ENT_XML1
            ) ?: ' '; // Prohibit empty string

            // Check if parsed attribute is an xml to import
            // This will override the nodeValue defined before
            $this->importXmlToNode($parsed, $attribute);

            $objRoot->appendChild($attribute);
        }

        return $objDom->saveXML();
    }


    /**
     * Camel Case (with first case uppercase) a column name
     *
     * @param string $strColName
     *
     * @return string
     */
    public static function camelCaseColName($strColName)
    {
        return preg_replace('/[\s\_\-]/', '', ucwords($strColName, ' _-'));
    }


    /**
     * Check whether the parsed attribute string is in XML and import the nodes if so
     *
     * @param string   $strParsedAttribute
     * @param \DOMNode $objAttributeNode
     *
     * @return bool True if xml was imported and appended to attribute False if nothing was changed
     */
    protected function importXmlToNode($strParsedAttribute, &$objAttributeNode)
    {
        if (!$strParsedAttribute) {
            return false;
        }

        libxml_use_internal_errors(true);

        $objDom = new \DOMDocument('1.0', 'utf-8');
        $objDom->loadXML($strParsedAttribute);
        $errors = libxml_get_errors();

        libxml_clear_errors();

        // It is a xml string
        if (empty($errors)) {
            // Reset node
            $objAttributeNode->nodeValue = '';

            foreach ($objDom->childNodes as $element) {
                $node = $objAttributeNode->ownerDocument->importNode($element, true);
                $objAttributeNode->appendChild($node);
            }

            return true;
        }

        return false;
    }


    /**
     * @param \MetaModels\IItem[]|\MetaModels\IItems $objOffers
     *
     * @return string
     */
    public function createICalForOffers($objOffers)
    {
        $vCalendar = new Calendar(\Environment::get('httpHost'));

        $arrICalProperties = deserialize($this->ical_fields);

        // Walk each offer
        while (null !== $objOffers && $objOffers->next()) {
            // Process published offers exclusively
            if (!$objOffers->getItem()->get('published')) {
                continue;
            }

            // filter by host
            //@todo real quick'n'dirty
            if ($this->id == 7 && $objOffers->getItem()->get('host')['id'] != 132) {
                continue;
            }

            $vEvent = new Event();

            /** @var array $arrProperty [ical_field] The property identifier
             *                          [metamodel_attribute] The property assigned MetaModel attribute name */
            foreach ($arrICalProperties as $arrProperty) {
                switch ($arrProperty['ical_field']) {
                    case 'dtStart':
                        try {
                            $objDate = new DateTime(
                                '@'.$objOffers->getItem()->get($arrProperty['metamodel_attribute'])
                            );
                            $vEvent->setDtStart($objDate);
                        } catch (\Exception $e) {
                            continue 3;
                        }
                        break;

                    case 'dtEnd':
                        try {
                            $objDate = new DateTime(
                                '@'.$objOffers->getItem()->get($arrProperty['metamodel_attribute'])
                            );
                            $vEvent->setDtEnd($objDate);
                        } catch (\Exception $e) {
                            continue 3;
                        }
                        break;

                    case 'summary':
                        $vEvent->setSummary($objOffers->getItem()->get($arrProperty['metamodel_attribute']));
                        break;

                    case 'description':
                        $vEvent->setDescription($objOffers->getItem()->get($arrProperty['metamodel_attribute']));
                        break;
                }
            }

            // skip events that pollute the calendar
            //@todo really quick'n'dirty
            $objDateStart = new DateTime('@'.$objOffers->getItem()->get('date'));
            $objDateEnd = new DateTime('@'.$objOffers->getItem()->get('date_end'));
            if ($objDateEnd->diff($objDateStart)->d > 1) {
                continue;
            }

            $vEvent->setUseTimezone(true);
            $vCalendar->addComponent($vEvent);
        }

        return $vCalendar->render();
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
