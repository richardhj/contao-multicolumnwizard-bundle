<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */
use NotificationCenter\Model\Notification;


/**
 * Table tl_ferienpass_attendancestatus
 */
/** @noinspection PhpUndefinedMethodInspection */
$GLOBALS['TL_DCA']['tl_ferienpass_attendancestatus'] = array
(

	// Config
	'config' => array
	(
		'dataContainer'             => 'Table',
		'label'                     => &$GLOBALS['TL_LANG']['FPMD']['attendancestatus'][0],
		'enableVersioning'          => true,
		'closed'                    => true,
		'onload_callback' => array
		(
			array('Ferienpass\Helper\Dca', 'addDefaultStatus'),
		),
		'sql' => array
		(
			'keys' => array
			(
				'id' => 'primary',
			)
		),
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
			'mode'                  => 1,
			'fields'                => array('type'),
			'flag'                  => 1,
			'panelLayout'           => 'filter;search,limit'
		),
		'label' => array
		(
			'fields'                => array('name', 'cssClass'),
			'format'                => '%s <span style="color:#b3b3b3;padding-left:3px">[%s]</span>',
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
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'             => &$GLOBALS['TL_LANG']['tl_ferienpass_attendancestatus']['edit'],
				'href'              => 'act=edit',
				'icon'              => 'edit.gif'
			),
			'show' => array
			(
				'label'             => &$GLOBALS['TL_LANG']['tl_ferienpass_attendancestatus']['show'],
				'href'              => 'act=show',
				'icon'              => 'show.gif'
			),
		)
	),

	// Palettes
	'palettes' => array
	(
		'default'                   => '{name_legend},name,type,title;{config_legend},notification_new,notification_onChange,cssClass',
	),

	// Fields
	'fields' => array
	(
        'id' => array
		(
			'sql'                 =>  "int(10) unsigned NOT NULL auto_increment",
		),
        'tstamp' => array
		(
			'sql'                 =>  "int(10) unsigned NOT NULL default '0'",
		),
        'name' => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_ferienpass_attendancestatus']['name'],
			'exclude'               => true,
			'inputType'             => 'text',
			'eval'                  => array('mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                   => "varchar(255) NOT NULL default ''",
		),
        'type'             => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_ferienpass_attendancestatus']['type'],
			'exclude'               => true,
			'inputType'             => 'select',
			'options'               => $GLOBALS['FERIENPASS_STATUS'],
			'eval'                  => array('tl_class'=>'w50', 'unique'=>true),
			'sql'                   => "varchar(64) NOT NULL default ''"
		),
        'title'            => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_ferienpass_attendancestatus']['title'],
			'exclude'               => true,
			'inputType'             => 'text',
			'eval'                  => array('maxlength'=>255, 'tl_class'=>'w50'),
			'sql'                   => "varchar(255) NOT NULL default ''",
		),
        'increasesCount'   => array
        (
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_attendancestatus']['increasesCount'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50'),
            'sql'       => "char(1) NOT NULL default ''",
        ),
        'locked'           => array
        (
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_attendancestatus']['locked'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => array('tl_class' => 'w50'),
            'sql'       => "char(1) NOT NULL default ''",
        ),
        'notification_new' => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_ferienpass_attendancestatus']['notification_new'],
			'exclude'               => true,
			'inputType'             => 'select',
			'options_callback'      => array('Ferienpass\Helper\Dca', 'getNotificationChoices'),
			'eval'                  => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'sql'                   => "int(10) unsigned NOT NULL default '0'",
			'relation'              => array('type' => 'hasOne', 'table' => Notification::getTable())
		),
        'notification_onChange' => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_ferienpass_attendancestatus']['notification_onChange'],
			'exclude'               => true,
			'inputType'             => 'select',
			'options_callback'      => array('Ferienpass\Helper\Dca', 'getNotificationChoices'),
			'eval'                  => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
			'sql'                   => "int(10) unsigned NOT NULL default '0'",
			'relation'              => array('type' => 'hasOne', 'table' => Notification::getTable())
		),
        'cssClass' => array
		(
			'label'                 => &$GLOBALS['TL_LANG']['tl_ferienpass_attendancestatus']['cssClass'],
			'exclude'               => true,
			'inputType'             => 'text',
			'eval'                  => array('tl_class'=>'w50', 'mandatory'=>true),
			'sql'                   => "varchar(255) NOT NULL default ''"
		),
	)
);
