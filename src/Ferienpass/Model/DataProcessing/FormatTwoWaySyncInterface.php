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


/**
 * Interface FormatTwoWaySyncInterface
 *
 * @package Ferienpass\Model\DataProcessing
 */
interface FormatTwoWaySyncInterface
{

    /**
     * @param array  $files
     * @param string $filesystem
     */
    public function backSyncFiles(array $files, string $filesystem): void;
}