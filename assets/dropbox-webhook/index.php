<?php

// Initialize the system
define('TL_MODE', 'FE');
require '../../../../../../system/initialize.php';

// Run the controller
$controller = new Ferienpass\Model\DataProcessing\DropboxWebhook();
$controller->handle();
