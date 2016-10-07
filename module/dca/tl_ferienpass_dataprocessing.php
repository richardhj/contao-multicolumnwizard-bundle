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
 * Table tl_ferienpass_dataprocessing
 */
$GLOBALS['TL_DCA']['tl_ferienpass_dataprocessing'] = array
(
	// Config
	'config'                => array
	(
		'dataContainer' => 'General',
		'sql'           => array
		(
			'keys' => array
			(
				'id' => 'primary'
			)
		)
	),

	// List
	'list'                  => array
	(
		'sorting'           => array
		(
			'mode'        => 1,
			'fields'      => array('name'),
			'flag'        => 1,
			'panelLayout' => 'filter;search,limit',
		),
		'label'             => array
		(
			'fields' => array('name', 'filesystem'),
			'format' => '%s <span class="tl_gray">[%s]</span>',
		),
		'global_operations' => array
		(
			'back' => array
			(
				'label'      => &$GLOBALS['TL_LANG']['MSC']['backBT'],
				'href'       => 'mod=&table=',
				'class'      => 'header_back',
				'attributes' => 'onclick="Backend.getScrollOffset();"',
			),
			'all'  => array
			(
				'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'       => 'act=select',
				'class'      => 'header_edit_all',
				'attributes' => 'onclick="Backend.getScrollOffset();"'
			),
		),
		'operations'        => array
		(
			'edit'   => array
			(
				'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['edit'],
				'href'  => 'act=edit',
				'icon'  => 'edit.gif',
			),
			'copy'   => array
			(
				'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['copy'],
				'href'  => 'act=copy',
				'icon'  => 'copy.gif'
			),
			'delete' => array
			(
				'label'      => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['delete'],
				'href'       => 'act=delete',
				'icon'       => 'delete.gif',
				'attributes' => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show'   => array
			(
				'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['show'],
				'href'  => 'act=show',
				'icon'  => 'show.gif'
			)
		)
	),

	// Meta Palettes
	'metapalettes'          => array
	(
		'default' => array
		(
			'title'      => array
			(
				'name',
			),
			'processing' => array
			(
				'type',
				'scope',
				'filesystem'
			)
		)
	),
	// Meta SubSelect Palettes
	'metasubselectpalettes' => array
	(
		'type'       => array
		(
			'xml'  => array
			(
				'metamodel_view',
				'combine_variants'
			),
			'ical' => array
			(
				'ical_fields',
			)
		),
		'scope'      => array
		(
			'single' => array(),
			'full'   => array
			(
				'offer_image_path',
				'host_logo_path'
			),
		),
		'filesystem' => array
		(
			'local'         => array
			(
				'export_file_name',
				'path_prefix',
				'sync'
			),
			'sendToBrowser' => array
			(
				'export_file_name'
			),
			'dropbox'       => array
			(
				'dropbox_access_token',
				'path_prefix',
				'sync'
			),
		)
	),

	// Fields
	'fields'                => array
	(
		'id'                   => array
		(
			'sql' => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp'               => array
		(
			'sql' => "int(10) unsigned NOT NULL default '0'"
		),
		'name'                 => array
		(
			'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['name'],
			'inputType' => 'text',
			'eval'      => array
			(
				'mandatory' => true,
				'maxlength' => 255,
				'tl_class'  => 'w50'
			),
			'sql'       => "varchar(255) NOT NULL default ''"
		),
		'type'                 => array
		(
			'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['type'],
			'inputType' => 'select',
			'default'   => 'xml',
			'options'   => array
			(
				'xml',
				'ical'
			),
			'reference' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['type_options'],
			'eval'      => array
			(
				'submitOnChange' => true,
				'tl_class'       => 'w50'
			),
			'sql'       => "varchar(64) NOT NULL default ''"
		),
		'metamodel_view'       => array
		(
			'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['metamodel_view'],
			'inputType'        => 'select',
			'options_callback' => array('Ferienpass\Helper\Dca', 'getOffersMetaModelRenderSettings'),
			'eval'             => array
			(
				'inlcudeBlankOption' => true,
				'tl_class'           => 'w50'
			),
			'sql'              => "int(10) NOT NULL default '0'"
		),
		'scope'                => array
		(
			'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['scope'],
			'inputType' => 'select',
			'options'   => array
			(
				'single',
				'full',
			),
			'eval'      => array
			(
				'submitOnChange' => true,
				'tl_class'       => 'w50'
			),
			'sql'       => "varchar(64) NOT NULL default ''"
		),
		'filesystem'           => array
		(
			'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['filesystem'],
			'inputType' => 'select',
			'options'   => array
			(
				'local',
				'sendToBrowser',
				'dropbox'
			),
			'reference' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['filesystem_options'],
			'eval'      => array
			(
				'submitOnChange' => true,
				'tl_class'       => 'w50 clr'
			),
			'sql'       => "varchar(64) NOT NULL default ''"
		),
		'offer_image_path'     => array
		(
			'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['offer_image_path'],
			'inputType' => 'fileTree',
			'eval'      => array
			(
				'fieldType' => 'radio',
				'files'     => false,
				'tl_class'  => 'w50 clr'
			),
			'sql'       => "binary(16) NULL"
		),
		'host_logo_path'       => array
		(
			'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['host_logo_path'],
			'inputType' => 'fileTree',
			'eval'      => array
			(
				'fieldType' => 'radio',
				'files'     => false,
				'tl_class'  => 'w50'
			),
			'sql'       => "binary(16) NULL"
		),
		'combine_variants'     => array
		(
			'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['combine_variants'],
			'inputType' => 'checkbox',
			'eval'      => array
			(
				'tl_class'       => 'w50 m12'
			),
			'sql'       => "char(1) NOT NULL default ''"
		),
		'export_file_name'     => array
		(
			'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['export_file_name'],
			'inputType' => 'text',
			'eval'      => array
			(
				'mandatory' => true,
				'tl_class'  => 'w50'
			),
			'sql'       => "varchar(255) NOT NULL default ''"
		),
		'dropbox_access_token' => array
		(
			'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['dropbox_access_token'],
			'inputType' => 'request_access_token',
			'eval'      => array
			(
				'tl_class' => 'long clr'
			),
			'sql'       => "varchar(255) NOT NULL default ''"
		),
		'dropbox_uid'          => array
		(
			'sql' => "int(10) NOT NULL default '0'"
		),
		'dropbox_cursor'       => array
		(
			'sql' => "varchar(255) NOT NULL default ''"
		),
		'path_prefix'          => array
		(
			'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['path_prefix'],
			'inputType' => 'text',
			'eval'      => array
			(
				'trailingSlash' => false,
				'tl_class'      => 'w50'
			),
			'sql'       => "varchar(255) NOT NULL default ''"
		),
		'sync'                 => array
		(
			'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['sync'],
			'inputType' => 'checkbox',
			'eval'      => array
			(
				'submitOnChange' => 'true',
				'tl_class'       => 'w50 m12'
			),
			'sql'       => "char(1) NOT NULL default ''"
		),
		'ical_fields'          => array
		(
			'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['ical_fields'],
			'inputType' => 'multiColumnWizard',
			'eval'      => array
			(
				'columnFields' => array
				(
					'ical_field'          => array
					(
						'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['ical_field'],
						'inputType' => 'select',
						'options'   => array
						(
							'dtStart',
							'dtEnd',
							'summary',
							'description',
							'location'
						),
						'eval'      => array('style' => 'width:250px', 'chosen' => true)
					),
					'metamodel_attribute' => array
					(
						'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['metamodel_attribute'],
						'inputType'        => 'conditionalselect',
						'options_callback' => array('Ferienpass\Helper\Dca', 'getMetaModelsAttributes'),
						'eval'             => array('condition' => 'mm_ferienpass', 'chosen' => true, 'style' => 'width:250px')
					),
				),
				'tl_class'     => 'clr'
			),
			'sql'       => "text NULL"
		),
	),
);
