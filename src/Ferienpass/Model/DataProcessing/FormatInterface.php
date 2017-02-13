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


interface FormatInterface
{
    public function __construct($model, $offers);

    /**
     * @return self
     */
    public function processOffers();


    /**
     * @return array
     */
    public function getFiles();

}