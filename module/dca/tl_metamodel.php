<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

$GLOBALS['TL_DCA']['tl_metamodel']['metapalettes']['default extends default']['+advanced'][] = 'owner_attribute';

$GLOBALS['TL_DCA']['tl_metamodel']['fields']['owner_attribute'] = array
(
	'label'            => &$GLOBALS['TL_LANG']['tl_metamodel']['owner_attribute'],
	'exclude'          => true,
	'inputType'        => 'select',
	'options_callback' => array('Ferienpass\Helper\Dca', 'getOwnerAttributeChoices'),
	'eval'             => array
	(
		'includeBlankOption' => true,
		'tl_class'           => 'w50'
	),
	'sql'              => "int(10) unsigned NOT NULL default '0'"
);
