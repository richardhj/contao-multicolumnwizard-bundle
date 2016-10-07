<?php

/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 *
 * @package      MetaModels
 * @subpackage   FilterText
 * @author       Christian de la Haye <service@delahaye.de>
 * @author       Andreas Isaak <info@andreas-isaak.de>
 * @author       Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author       David Molineus <mail@netzmacht.de>
 * @author       Christopher Boelter <christopher@boelter.eu>
 * @copyright    The MetaModels team.
 * @license      LGPL.
 * @filesource
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
