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


/**
 * Interface TwoWaySyncInterface
 *
 * @package Ferienpass\Model\DataProcessing\Filesystem
 */
interface TwoWaySyncInterface
{

    public function triggerBackSync(): void;
}