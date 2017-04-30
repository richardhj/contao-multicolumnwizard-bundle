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

    /**
     * {@inheritdoc}
     */
    public function __construct(DataProcessing $model, IItems $items)
    {
        $this->model  = $model;
        $this->offers = $items;
    }

    /**
     * @return DataProcessing|\Model
     */
    public function getModel(): DataProcessing
    {
        return $this->model;
    }

    /**
     * @return IItems
     */
    public function getOffers(): IItems
    {
        return $this->offers;
    }

    /**
     * {@inheritdoc}
     */
    public function processFiles(array $files): void
    {
        // Generate a zip file
        $zipWriter = new \ZipWriter($this->getModel()->getTmpPath() . '/export.zip');

        foreach ($files as $file) {
            $path = str_replace($this->getModel()->getTmpPath() . '/', '', $file['path']);
            $zipWriter->addFile($path);
        }

        $zipWriter->close();

        // Output ZIP
        header('Content-type: application/octetstream');
        header('Content-Disposition: attachment; filename="' . $this->getModel()->export_file_name . '.zip"');
        readfile(TL_ROOT . '/' . $this->getModel()->getTmpPath() . '/export.zip');
        exit;
    }
}
