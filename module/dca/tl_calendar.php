<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_calendar']['palettes']['__selector__'][] = 'addMetamodel';
$GLOBALS['TL_DCA']['tl_calendar']['palettes']['default'] .= ';{metamodel_legend},addMetamodel';
$GLOBALS['TL_DCA']['tl_calendar']['subpalettes']['addMetamodel'] = 'metamodel,metamodelFields';


/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_calendar']['fields']['addMetamodel'] = array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_calendar']['addMetaModel'],
	'exclude'   => true,
	'inputType' => 'checkbox',
	'eval'      => array('submitOnChange' => true, 'tl_class' => 'w50'),
	'sql'       => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_calendar']['fields']['metamodel'] = array
(
	'label'            => &$GLOBALS['TL_LANG']['tl_calendar']['metamodel'],
	'exclude'          => true,
	'inputType'        => 'select',
	'options_callback' => array('\Ferienpass\Helper\Dca', 'getMetaModels'),
	'eval'             => array('tl_class' => 'w50'),
	'sql'              => "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_calendar']['fields']['metamodelFields'] = array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_calendar']['metamodelFields'],
	'exclude'   => true,
	'inputType' => 'multiColumnWizard',
	'eval'      => array
	(
		'columnFields' => array
		(
			'calendar_field'     => array
			(
				'label'            => &$GLOBALS['TL_LANG']['tl_theme']['ts_client_os'],
				'exclude'          => true,
				'inputType'        => 'select',
				'options_callback' => array('Ferienpass\Helper\Events', 'getEventAttributesTranslated'),
				'eval'             => array('style' => 'width:250px', 'chosen' => true)
			),
			'metamodel_field' => array
			(
				'label'            => &$GLOBALS['TL_LANG']['tl_theme']['ts_client_browser'],
				'exclude'          => true,
				'inputType'        => 'conditionalselect',
				'options_callback' => array('Ferienpass\Helper\Dca', 'getMetaModelsAttributes'),
				'eval'             => array('conditionField' => 'metamodel', 'chosen' => true, 'style' => 'width:250px')
			),
		)
	),
	'sql'       => "text NULL"
);
