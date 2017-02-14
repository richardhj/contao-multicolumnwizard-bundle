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
     * {@inheritdoc}
     */
    public function __construct(DataProcessing $model, IItems $offers)
    {
        $this->model  = $model;
        $this->offers = $offers;
    }

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
    public function processFiles(array $files)
    {
        $pathPrefix = ($this->getModel()->path_prefix) ? $this->getModel()->path_prefix . '/' : '';

        if (array_is_assoc($files)) {
            foreach ($files as $directory => $arrFiles) {
                foreach ($arrFiles as $file) {
                    $this->getModel()->getMountManager()->put(
                        'local://share/' . $pathPrefix . $directory . '/' . $file['basename'],
                        $this->getModel()->getMountManager()->read('local://' . $file['path'])
                    );
                }
            }
        } else {
            foreach ($files as $file) {
                $this->getModel()->getMountManager()->put(
                    'local://share/' . $pathPrefix . $file['basename'],
                    $this->getModel()->getMountManager()->read('local://' . $file['path'])
                );
            }
        }

    }
}