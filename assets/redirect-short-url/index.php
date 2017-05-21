<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

// Initialize the system
define('TL_MODE', 'FE');
require '../../../../../../system/initialize.php';

// Run the controller
$controller = new Ferienpass\FrontendIntegration\RedirectShortUrl();
$controller->handle();
