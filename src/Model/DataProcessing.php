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
use Doctrine\DBAL\Connection;
use MetaModels\Filter\Setting\IFilterSettingFactory;
use MetaModels\IFactory;
use MetaModels\Render\Setting\IRenderSettingFactory;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem\Dropbox;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem\Local;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem\SendToBrowser;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\FilesystemInterface;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format\ICal;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format\Xml;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\FormatInterface;
use MetaModels\Filter\IFilter;
use RuntimeException;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Filesystem\Filesystem;


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
     * @var FormatInterface
     */
    private $formatHandler;

    /**
     * @var FilesystemInterface
     */
    private $fileSystemHandler;

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

    /**
     * @var IRenderSettingFactory
     */
    private $renderSettingFactory;

    /**
     * @var Filesystem
     */
    private $filesystemUtil;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var string
     */
    private $kernelProjectDir;

    /**
     * DataProcessing constructor.
     *
     * @param \Database\Result|null $objResult
     *
     * @throws InvalidArgumentException
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    public function __construct(\Database\Result $objResult = null)
    {
        parent::__construct($objResult);

        $this->metaModelFactory     = System::getContainer()->get('metamodels.factory');
        $this->filterSettingFactory = System::getContainer()->get('metamodels.filter_setting_factory');
        $this->renderSettingFactory = System::getContainer()->get('metamodels.render_setting_factory');
        $this->connection           = System::getContainer()->get('database_connection');
        $this->dispatcher           = System::getContainer()->get('event_dispatcher');
        $this->filesystemUtil       = System::getContainer()->get('filesystem');
        $this->kernelProjectDir     = System::getContainer()->getParameter('kernel.project_dir');
    }

    /**
     * @return FormatInterface
     */
    public function getFormatHandler(): FormatInterface
    {
        if (null === $this->formatHandler) {
            switch ($this->format) {
                case 'xml':
                    $this->formatHandler = new Xml(
                        $this->renderSettingFactory,
                        $this->filesystemUtil,
                        $this->connection,
                        $this->dispatcher,
                        $this->kernelProjectDir
                    );
                    break;

                case 'ical':
                    $this->formatHandler = new ICal($this->filesystemUtil, $this->kernelProjectDir);
                    break;
            }
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
                    $this->fileSystemHandler = new Local($this->filesystemUtil, $this->kernelProjectDir);
                    break;

                case 'sendToBrowser':
                    $this->fileSystemHandler = new SendToBrowser($this->kernelProjectDir);
                    break;

                case 'dropbox':
                    $this->fileSystemHandler = new Dropbox($this->kernelProjectDir);
                    break;
            }
        }

        return $this->fileSystemHandler;
    }

    /**
     * @return string
     */
    public function getTmpPath(): string
    {
        if (null === $this->tmpPath) {
            $this->tmpPath = 'system/tmp/'.uniqid('', true);
        }

        return $this->tmpPath;
    }

    /**
     * @return IFilter
     *
     * @throws RuntimeException
     */
    public function getFilter(): IFilter
    {
        if (null === $this->filter) {
            $metaModel = $this->metaModelFactory->getMetaModel('mm_ferienpass');
            if (null === $metaModel) {
                throw new RuntimeException('Could not instantiate MetaModel: mm_ferienpass');
            }

            $this->filter = $metaModel->getEmptyFilter();
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
     * @throws \Exception
     */
    public function run(): void
    {
        $metaModel = $this->metaModelFactory->getMetaModel('mm_ferienpass');
        if (null === $metaModel || null === $filter = $this->getFilter()) {
            throw new RuntimeException('Could not instantiate MetaModel: mm_ferienpass');
        }

        // Provide filter
        $this->filterSettingFactory
            ->createCollection($this->metamodel_filtering)
            ->addRules(
                $filter,
                $this->getFilterParams()
            );

        // Find items by filter
        $items = $metaModel
            ->findByFilter(
                $filter,
                $this->metamodel_sortby,
                $this->metamodel_offset,
                $this->metamodel_limit,
                $this->metamodel_sortby_direction
            );

        // Fetch files from format handler
        $files = $this
            ->getFormatHandler()
            ->processItems($items, $this);

        $files = array_merge(
            $files,
            $this->fetchStaticFiles()
        );

        // Process files
        $this
            ->getFileSystemHandler()
            ->processFiles($files, $this);

        // Delete tmp path
        $this->filesystemUtil->remove($this->kernelProjectDir.'/'.$this->getTmpPath());
    }

    /**
     * @return array
     */
    protected function fetchStaticFiles(): array
    {
        $files = [];

        foreach ((array)deserialize($this->static_dirs, true) as $dirBin) {
            $path    = \FilesModel::findByPk($dirBin)->path;
            $files[] = scandir($this->kernelProjectDir.'/'.$path, SCANDIR_SORT_NONE);
        }

        if ([] === $files) {
            return [];
        }

        $files = array_merge(...$files);

        return $files;
    }
}
