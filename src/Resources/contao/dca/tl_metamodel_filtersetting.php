<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


// Age
$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['metapalettes']['age extends default'] = array
(
	'+config' => array('attr_id', 'urlparam', 'label', 'template'),
);

// Attendance available
$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['metapalettes']['attendance_available extends _attribute_']['+config'][] =
	'urlparam';

$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['metapalettes']['attendance_available extends _attribute_']['+fefilter'][] =
	'label';
$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['metapalettes']['attendance_available extends _attribute_']['+fefilter'][] =
	'template';
$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['metapalettes']['attendance_available extends _attribute_']['+fefilter'][] =
	'ynmode';
