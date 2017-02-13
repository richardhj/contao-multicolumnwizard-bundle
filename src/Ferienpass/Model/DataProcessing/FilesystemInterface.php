<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Model\DataProcessing;


interface FilesystemInterface
{
    public function __construct($model, $offers);


    /**
     * @param array $files
     *
     * @return void
     */
    public function processFiles(array $files);
}