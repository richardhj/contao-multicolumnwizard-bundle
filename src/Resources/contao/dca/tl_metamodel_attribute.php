<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


/**
 * Table tl_metamodel_attribute
 */
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['age extends _simpleattribute_'] = array();
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['offer_date extends _simpleattribute_'] = [];
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['alias_trigger_sync extends alias'] = array
(
	'+advanced' => array
	(
		'data_processing'
	)
);

//$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['data_processing'] = array
//(
//	'inputType'  => 'select',
//	'options_callback' => array('Richardhj\ContaoFerienpassBundle\Helper\Dca', 'getDataProcessingChoices'),
//	'eval' => array
//	(
//		'mandatory' => true
//	),
//	'sql'        => "int(10) NOT NULL default '0'"
//);
