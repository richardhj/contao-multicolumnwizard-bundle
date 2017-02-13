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


use Dropbox\Exception_BadRequest;
use Dropbox\Exception_NetworkIO;
use Ferienpass\Model\DataProcessing;
use Ferienpass\Model\DataProcessing\FilesystemInterface;
use MetaModels\IItems;

class Dropbox implements FilesystemInterface
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


    public function processFiles(array $files)
    {
        // Make sure to mount dropbox
        $objMountManger = $this->getModel()->getMountManager('dropbox');

        foreach ($files as $directory => $arrFiles) {
            foreach ($arrFiles as $file) {
                try {
                    $objMountManger->put(
                        'dropbox://' . $directory . '/' . $file['basename'],
                        $objMountManger->read('local://' . $file['path'])
                    );

                } catch (Exception_BadRequest $e) {
                    // File was not uploaded
                    // often because it is on the ignored file list
                    \System::log(
                        sprintf('%s. Data processing ID %u', $e->getMessage(), $this->getModel()->id),
                        __METHOD__,
                        TL_GENERAL
                    );
                } catch (Exception_NetworkIO $e) {
                    // File was not uploaded
                    // Connection refused
                    \System::log(
                        sprintf('%s. Data processing ID %u', $e->getMessage(), $this->getModel()->id),
                        __METHOD__,
                        TL_ERROR
                    );
                }
            }
        }
    }


}