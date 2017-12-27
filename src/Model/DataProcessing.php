<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Richardhj\ContaoFerienpassBundle\Model;

use Contao\Model;
use Dropbox\Client as DropboxClient;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem\Dropbox;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem\Local;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem\SendToBrowser;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\FilesystemInterface;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format\ICal;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format\Xml;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\FormatInterface;
use League\Flysystem\Dropbox\DropboxAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\MountManager;
use MetaModels\Filter\IFilter;
use MetaModels\IItems;
use MetaModels\IMetaModel;


/**
 * @property string  $name
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
 * @property string  $format
 * @property boolean $combine_variants
 * @property mixed   $variant_delimiters
 * @property boolean $xml_single_file
 * @property integer $metamodel_view
 * @property int     $metamodel_filtering
 * @property mixed   $metamodel_filterparams
 * @property string  $metamodel_sortby
 * @property int     $metamodel_offset
 * @property int     $metamodel_limit
 * @property string  $metamodel_sortby_direction
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
     * @var IMetaModel
     */
    private $metaModel;


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
    private $items;


    /**
     * @var IFilter
     */
    private $filter;


    /**
     * @var array
     */
    private $filterParams;


    /**
     * @var string
     */
    private $tmpPath;


    /**
     * @return FormatInterface
     */
    public function getFormatHandler(): FormatInterface
    {
        if (null === $this->formatHandler) {
            switch ($this->format) {
                case 'xml':
                    $this->formatHandler = new Xml($this);
                    break;

                case 'ical':
                    $this->formatHandler = new ICal($this);
                    break;
            }
        }

        if (null !== $this->items) {
            $this->formatHandler->setItems($this->getItems());
        }

        return $this->formatHandler;
    }

    /**
     * @return FilesystemInterface
     */
    public function getFileSystemHandler(): FilesystemInterface
    {
        if (null === $this->fileSystemHandler) {
            switch ($this->filesystem) {
                case 'local':
                    $this->fileSystemHandler = new Local($this);
                    break;

                case 'sendToBrowser':
                    $this->fileSystemHandler = new SendToBrowser($this);
                    break;

                case 'dropbox':
                    $this->fileSystemHandler = new Dropbox($this);
                    break;
            }
        }

        if (null !== $this->items) {
            $this->fileSystemHandler->setItems($this->getItems());
        }

        return $this->fileSystemHandler;
    }


    /**
     * @param array|string $varFilesystems The filesystem(s) you want to be mounted
     *
     * @return MountManager
     */
    public function getMountManager($varFilesystems = null): MountManager
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
     * @param string $fileSystem
     *
     * @return \League\Flysystem\FilesystemInterface
     */
    public function getFileSystem($fileSystem): \League\Flysystem\FilesystemInterface
    {
        return $this
            ->getMountManager($fileSystem)
            ->getFilesystem($fileSystem);
    }


    /**
     * @return IMetaModel
     */
    public function getMetaModel(): IMetaModel
    {
        if (null === $this->metaModel) {
            $this->metaModel = Offer::getInstance()->getMetaModel();
        }

        return $this->metaModel;
    }


    /**
     * @return string
     */
    public function getTmpPath(): string
    {
        if (null === $this->tmpPath) {
            $this->tmpPath = 'system/tmp/' . time();
        }

        return $this->tmpPath;
    }

    /**
     * @return IItems
     */
    public function getItems(): IItems
    {
        return $this->items;
    }

    /**
     * @return IFilter
     */
    public function getFilter(): IFilter
    {
        if (null === $this->filter) {
            $this->filter = $this
                ->getMetaModel()
                ->getEmptyFilter();
        }

        return $this->filter;
    }

    /**
     * @param IFilter $filter
     *
     * @return self
     */
    public function setFilter(IFilter $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Process the widget value to MetaModels conform filter params
     *
     * @return array
     */
    public function getFilterParams(): array
    {
        if (null === $this->filterParams) {
            $this->filterParams = array_map(
                function ($param) {
                    return $param['value'];
                },
                deserialize($this->metamodel_filterparams, true)
            );
        }

        return $this->filterParams;
    }


    /**
     * @param array $filterParams
     *
     * @return self
     */
    public function setFilterParams(array $filterParams): self
    {
        $this->filterParams = $filterParams;

        return $this;
    }


    /**
     * Run data processing by its configuration
     */
    public function run()
    {
        // Provide filter
        $this
            ->getMetaModel()
            ->getServiceContainer()
            ->getFilterFactory()
            ->createCollection($this->metamodel_filtering)
            ->addRules(
                $this->getFilter(),
                $this->getFilterParams()
            );

        // Find items by filter
        $this->items = $this
            ->getMetaModel()
            ->findByFilter(
                $this->getFilter(),
                $this->metamodel_sortby,
                $this->metamodel_offset,
                $this->metamodel_limit,
                $this->metamodel_sortby_direction
            );

        // Fetch files from format handler
        $files = $this
            ->getFormatHandler()
            ->processItems()
            ->getFiles();

        $files = array_merge(
            $files,
            $this->fetchStaticFiles()
        );

        // Process files
        $this
            ->getFileSystemHandler()
            ->processFiles($files);

        // Delete tmp path
        $this
            ->getMountManager()
            ->deleteDir('local://' . $this->getTmpPath());
    }


    /**
     * @return array
     */
    protected function fetchStaticFiles(): array
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
                $client  = new DropboxClient(
                    $this->dropbox_access_token,
                    $container['ferienpass.dropbox.appSecret']
                );
                $adapter = new DropboxAdapter(
                    $client,
                    'ferienpass.online/' . $this->path_prefix
                );

                $this
                    ->getMountManager()
                    ->mountFilesystem($fileSystem, new Filesystem($adapter));
                break;
        }
    }
}
