<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\Model;

use Contao\Model;
use Contao\System;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\IFactory;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem\Dropbox;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem\Local;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem\SendToBrowser;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\FilesystemInterface;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format\ICal;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format\Xml;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\FormatInterface;
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
     * @var IFactory
     */
    private $metaModelFactory;

    /**
     * @var IFilterSettingFactory
     */
    private $filterSettingFactory;

    public function __construct(\Database\Result $objResult=null)
    {
        parent::__construct($objResult);

        $this->metaModelFactory = System::getContainer()->get('metamodels.factory');
        $this->filterSettingFactory = System::getContainer()->get('metamodels.filter_setting_factory');
    }

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
     * @return string
     */
    public function getTmpPath(): string
    {
        if (null === $this->tmpPath) {
            $this->tmpPath = 'system/tmp/'.time();
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
     * @throws \RuntimeException
     */
    public function getFilter(): IFilter
    {
        if (null === $this->filter) {
            $metaModel = $this->metaModelFactory->getMetaModel('mm_ferienpass');
            if (null === $metaModel) {
                throw new \RuntimeException('Cannot instantiate MetaModel: mm_ferienpass');
            }

            $this->filter = $metaModel
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
     *
     * @throws \League\Flysystem\RootViolationException
     * @throws \RuntimeException
     */
    public function run(): void
    {
        $metaModel = $this->metaModelFactory->getMetaModel('mm_ferienpass');
        if (null === $metaModel) {
            throw new \RuntimeException('Could not instantiate MetaModel: mm_ferienpass');
        }

        // Provide filter
        $this->filterSettingFactory
            ->createCollection($this->metamodel_filtering)
            ->addRules(
                $this->getFilter(),
                $this->getFilterParams()
            );

        // Find items by filter
        $this->items = $metaModel
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

//        $files = array_merge(
//            $files,
//            $this->fetchStaticFiles()
//        );

        // Process files
        $this
            ->getFileSystemHandler()
            ->processFiles($files);

        // Delete tmp path

//        /** @var \League\Flysystem\FilesystemInterface $filesystem */
//        $filesystem = System::getContainer()->get('ferienpass_local_filesystem');
//        $filesystem->deleteDir('local://'.$this->getTmpPath());
    }


    /**
     * @return array
     */
    protected function fetchStaticFiles(): array
    {
        $files = [];
        /** @var \League\Flysystem\FilesystemInterface $filesystem */
        $filesystem = System::getContainer()->get('ferienpass_local_filesystem');

        foreach ((array)deserialize($this->static_dirs, true) as $dirBin) {
            $path    = \FilesModel::findByPk($dirBin)->path;
            $files[] = $filesystem->listContents($path, true);
        }

        $files = array_merge(...$files);

        return $files;
    }
}
