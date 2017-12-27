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
 * Class SendToBrowser
 *
 * @package Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem
 */
class SendToBrowser implements FilesystemInterface
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
     * {@inheritdoc}
     */
    public function __construct(DataProcessing $model)
    {
        $this->model  = $model;
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
    public function getItems(): IItems
    {
        return $this->items;
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
    public function processFiles(array $files)
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
