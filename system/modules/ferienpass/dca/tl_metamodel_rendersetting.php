<?php

/**
 * Table tl_metamodel_rendersetting
 */
$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['metapalettes']['timestamp extends default']['timesettings'][] = 'outputAge';

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['outputAge'] = array(
	'label'              => &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['outputAge'],
	'exclude'            => true,
	'inputType'          => 'checkbox',
	'eval' => array(
		'tl_class'       => 'w50 m12',
	),
	'sql'                => "char(1) NOT NULL default ''"
);
