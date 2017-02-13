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
use Ferienpass\Model\DataProcessing\Filesystem\Dropbox;
use Ferienpass\Model\DataProcessing\Filesystem\Local;
use Ferienpass\Model\DataProcessing\Filesystem\SendToBrowser;
use Ferienpass\Model\DataProcessing\FilesystemInterface;
use Ferienpass\Model\DataProcessing\Format\ICal;
use Ferienpass\Model\DataProcessing\Format\Xml;
use Ferienpass\Model\DataProcessing\FormatInterface;
use League\Flysystem\Dropbox\DropboxAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
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
        return 'file://../' . static::EXPORT_OFFER_IMAGES_PATH . '/' . $strFileName;
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
        return 'file://../' . static::EXPORT_HOST_LOGOS_PATH . '/' . $strFileName;
    }


    /**
     * @return FormatInterface
     */
    public function getFormatHandler()
    {
        if (null === $this->formatHandler) {
            switch ($this->type) {
                case 'xml':
                    $this->formatHandler = new Xml($this, $this->getOffers());
                    break;

                case 'ical':
                    $this->formatHandler = new ICal($this, $this->getOffers());
                    break;
            }
        }

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
        if (null === $this->tmpPath) {
            $this->tmpPath = 'system/tmp/' . time();
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
                    : Offer::getInstance()->findMultipleByIds((array) $arrOffers);
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
        $this->getMountManager()->deleteDir('local://' . $this->getTmpPath());
    }


    /**
     * @param array|string $varFilesystems The filesystem(s) you want to be mounted
     *
     * @return MountManager
     */
    public function getMountManager($varFilesystems = null)
    {
        if (null !== $varFilesystems) {
            foreach ((array) $varFilesystems as $filesystem) {
                $this->mountFileSystem($filesystem);
            }
        }

        return $GLOBALS['container']['flysystem.mount-manager'];
    }


    /**
     * @param string $fileSystem
     */
    protected function mountFileSystem($fileSystem)
    {
        global $container;

        switch ($fileSystem) {
            case 'local':
            case 'dbafs':
                break;

            case 'dropbox':
                $client  = new Client($this->dropbox_access_token, $container['ferienpass.dropbox.appSecret']);
                $adapter = new DropboxAdapter($client, $this->path_prefix ?: null);

                $this
                    ->getMountManager()
                    ->mountFilesystem($fileSystem, new Filesystem($adapter));
                break;
        }
    }


    /**
     * @param string $fileSystem
     *
     * @return \League\Flysystem\FilesystemInterface
     */
    protected function getFileSystem($fileSystem)
    {
        return $this
            ->getMountManager($fileSystem)
            ->getFilesystem($fileSystem);
    }
}
