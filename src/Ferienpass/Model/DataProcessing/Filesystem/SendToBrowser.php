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

class SendToBrowser implements FilesystemInterface
{

    /**
     * @var DataProcessing|\Model $model
     */
    private $model;

    /**
     * @var IItems $offers
     */
    private $offers;


    public function __construct($model, $offers)
    {
        $this->model  = $model;
        $this->offers = $offers;
    }


    public function processFiles(array $files)
    {
        // Generate a zip file
        $objZip = new \ZipWriter($this->getModel()->getTmpPath() . '/export.zip');

        if (array_is_assoc($files)) {
            foreach ($files as $directory => $arrFiles) {
                foreach ($arrFiles as $file) {
                    $objZip->addFile($file['path'], $directory . '/' . $file['basename']);
                }
            }
        } else {
            foreach ($files as $file) {
                $objZip->addFile($file['path'], $file['basename']);
            }
        }

        $objZip->close();

        // Output ZIP
        header('Content-type: application/octetstream');
        header('Content-Disposition: attachment; filename="' . $this->getModel()->export_file_name . '.zip"');
        readfile(TL_ROOT . '/' . $this->getModel()->getTmpPath() . '/export.zip');
        exit;

    }

    /**
     * @return DataProcessing|\Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @return IItems
     */
    public function getOffers()
    {
        return $this->offers;
    }

}