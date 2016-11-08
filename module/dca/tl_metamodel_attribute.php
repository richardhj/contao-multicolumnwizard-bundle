<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 *
 * @package     MetaModels
 * @subpackage  AttributeForeignKey
 * @author      Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author      Andreas Isaak <info@andreas-isaak.de>
 * @author      Christopher Boelter <christopher@boelter.eu>
 * @copyright   The MetaModels team.
 * @license     LGPL.
 * @filesource
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
//	'options_callback' => array('Ferienpass\Helper\Dca', 'getDataProcessingChoices'),
//	'eval' => array
//	(
//		'mandatory' => true
//	),
//	'sql'        => "int(10) NOT NULL default '0'"
//);
