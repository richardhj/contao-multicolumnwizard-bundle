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
 * Table tl_ferienpass_document
 */
$GLOBALS['TL_DCA']['tl_ferienpass_document'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'             => 'Table',
		'enableVersioning'          => true,
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary'
			)
		)
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                  => 1,
			'fields'                => array('name'),
			'flag'                  => 1,
			'panelLayout'           => 'filter;search,limit',
		),
		'label' => array
		(
			'fields'                => array('name'),
			'format'                => '%s',
		),
		'global_operations' => array
		(
			'back' => array
			(
				'label'             => &$GLOBALS['TL_LANG']['MSC']['backBT'],
				'href'              => 'mod=&table=',
				'class'             => 'header_back',
				'attributes'        => 'onclick="Backend.getScrollOffset();"',
			),
			'new' => array
			(
				'label'             => &$GLOBALS['TL_LANG']['tl_ferienpass_document']['new'],
				'href'              => 'act=create',
				'class'             => 'header_new',
				'attributes'        => 'onclick="Backend.getScrollOffset();"',
			),
			'all' => array
			(
				'label'             => &$GLOBALS['TL_LANG']['MSC']['all'],
				'href'              => 'act=select',
				'class'             => 'header_edit_all',
				'attributes'        => 'onclick="Backend.getScrollOffset();"'
			),
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'             => &$GLOBALS['TL_LANG']['tl_ferienpass_document']['edit'],
				'href'              => 'act=edit',
				'icon'              => 'edit.gif',
			),
			'copy' => array
			(
				'label'             => &$GLOBALS['TL_LANG']['tl_ferienpass_document']['copy'],
				'href'              => 'act=copy',
				'icon'              => 'copy.gif'
			),
			'delete' => array
			(
				'label'             => &$GLOBALS['TL_LANG']['tl_ferienpass_document']['delete'],
				'href'              => 'act=delete',
				'icon'              => 'delete.gif',
				'attributes'        => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array
			(
				'label'             => &$GLOBALS['TL_LANG']['tl_ferienpass_document']['show'],
				'href'              => 'act=show',
				'icon'              => 'show.gif'
			)
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                   => '{type_legend},name,type;{config_legend},documentTitle,fileTitle;{template_legend},documentTpl,gallery,collectionTpl,orderCollectionBy',
	),

	// Fields
	'fields' => array
	(
		'id' => array
		(
			'sql'                   => "int(10) unsigned NOT NULL auto_increment"
		),
		'tstamp' => array
		(
			'sql'                   => "int(10) unsigned NOT NULL default '0'"
		),
		'name' => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_ferienpass_document']['name'],
			'exclude'               => true,
			'inputType'             => 'text',
			'eval'                  => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                   => "varchar(255) NOT NULL default ''"
		),
		'documentTitle' => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_ferienpass_document']['documentTitle'],
			'exclude'               => true,
			'inputType'             => 'text',
			'eval'                  => array('mandatory'=>true, 'decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                   => "varchar(255) NOT NULL default ''"
		),
		'fileTitle' => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_ferienpass_document']['fileTitle'],
			'exclude'               => true,
			'inputType'             => 'text',
			'eval'                  => array('mandatory'=>true, 'decodeEntities'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                   => "varchar(255) NOT NULL default ''"
		),
		'documentTpl'  => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_ferienpass_document']['documentTpl'],
			'exclude'               => true,
			'inputType'             => 'select',
			'options_callback'      => function(\DataContainer $dc) {
				return \Controller::getTemplateGroup('fp_document_');
			},
			'eval'                  => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50', 'mandatory'=>true),
			'sql'                   => "varchar(64) NOT NULL default ''",
		),
		'collectionTpl'  => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_ferienpass_document']['collectionTpl'],
			'exclude'               => true,
			'default'               => 'iso_collection_invoice',
			'inputType'             => 'select',
			'options_callback'      => function(\DataContainer $dc) {
				return \Controller::getTemplateGroup('fp_collection_');
			},
			'eval'                  => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50', 'mandatory'=>true),
			'sql'                   => "varchar(64) NOT NULL default ''",
		),
		'orderCollectionBy' => array //FIXME
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_ferienpass_document']['orderCollectionBy'],
			'exclude'               => true,
			'default'               => 'asc_id',
			'inputType'             => 'select',
			'options'               => $GLOBALS['TL_LANG']['MSC']['iso_orderCollectionBy'],
			'eval'                  => array(/*'mandatory'=>true,*/ 'tl_class'=>'w50'),
			'sql'                   => "varchar(16) NOT NULL default ''",
		)
	),
);