<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


namespace Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem;


use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\FilesystemInterface;
use MetaModels\IItems;


/**
 * Class Local
 *
 * @package Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem
 */
class Local implements FilesystemInterface
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
     * @return DataProcessing|\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param IItems $items
     *
     * @return FilesystemInterface
     */
    public function setItems(IItems $items): FilesystemInterface
    {
        $this->items = $items;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function __construct(DataProcessing $model)
    {
        $this->model  = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function processFiles(array $files)
    {
        $pathPrefix = ($this->getModel()->path_prefix) ? $this->getModel()->path_prefix . '/' : '';

        foreach ($files as $file) {
            $path = str_replace($this->getModel()->getTmpPath() . '/', '', $file['path']);
            $this->getModel()->getMountManager()->put(
                'local://share/' . $pathPrefix . $path,
                $this->getModel()->getMountManager()->read('local://' . $file['path'])
            );
        }
    }
}