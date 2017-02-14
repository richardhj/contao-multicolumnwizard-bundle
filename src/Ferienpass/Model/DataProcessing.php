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
 * @property mixed   $static_dirs
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

        $files = array_merge(
            $files,
            $this->fetchStaticFiles()
        );

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
        global $container;

        if (null !== $varFilesystems) {
            foreach ((array) $varFilesystems as $filesystem) {
                $this->mountFileSystem($filesystem);
            }
        }

        return $container['flysystem.mount-manager'];
    }


    /**
     * @return array
     */
    protected function fetchStaticFiles()
    {
        $files      = [];
        $fileSystem = $this->getFileSystem('local');

        foreach (deserialize($this->static_dirs, true) as $dirBin) {
            $path = (\FilesModel::findByPk($dirBin))->path;

            $files = array_merge(
                $files,
                $fileSystem->listContents($path)
            );
        }

        return $files;
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
                $client  = new Client(
                    $this->dropbox_access_token,
                    $container['ferienpass.dropbox.appSecret']
                );
                $adapter = new DropboxAdapter(
                    $client,
                    rtrim('Ferienpass/' . $this->path_prefix, '/')
                );

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
