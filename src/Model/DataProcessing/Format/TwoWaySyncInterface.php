<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format;


/**
 * Interface FormatTwoWaySyncInterface
 *
 * @package Richardhj\ContaoFerienpassBundle\Model\DataProcessing
 */
interface TwoWaySyncInterface
{

    /**
     * @param array  $files
     * @param string $originFileSystem
     */
    public function backSyncFiles(array $files, string $originFileSystem);
}