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


use Ferienpass\Model\DataProcessing;
use MetaModels\IItems;

interface FilesystemInterface
{

    /**
     * FilesystemInterface constructor.
     *
     * @param DataProcessing $model
     * @param IItems         $items
     */
    public function __construct(DataProcessing $model, IItems $items);

    /**
     * @param array $files
     *
     * @return void
     */
    public function processFiles(array $files);
}
