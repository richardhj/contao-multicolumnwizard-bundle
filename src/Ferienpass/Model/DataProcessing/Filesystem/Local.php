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


use Ferienpass\Model\DataProcessing;
use Ferienpass\Model\DataProcessing\FilesystemInterface;
use MetaModels\IItems;

class Local implements FilesystemInterface
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
     * @return DataProcessing|\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * {@inheritdoc}
     */
    public function __construct(DataProcessing $model, IItems $items)
    {
        $this->model  = $model;
        $this->offers = $items;
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