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


$table = Ferienpass\Model\AttendanceStatus::getTable();


/** @noinspection PhpUndefinedMethodInspection */
$GLOBALS['TL_DCA'][$table] = [

    // Config
    'config'       => [
        'dataContainer'   => 'General',
        'notCreatable'    => true,
        'notDeletable'    => true,
        'onload_callback' => [
            ['Ferienpass\Helper\Dca', 'addDefaultStatus'],
        ],
        'sql'             => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    // List
    'list'         => [
        'sorting'           => [
            'mode'        => 1,
            'fields'      => ['type'],
            'panelLayout' => 'limit',
        ],
        'label'             => [
            'fields'      => [
                'type',
                'title',
                'notification_new',
                'notification_onChange',
                'cssClass',
                'locked',
                'increasesCount',
                'enableManualAssignment',
            ],
            'showColumns' => true,
        ],
        'global_operations' => [
            'back' =>
                [
                    'label'      => &$GLOBALS['TL_LANG']['MSC']['backBT'],
                    'href'       => 'mod=&table=',
                    'class'      => 'header_back',
                    'attributes' => 'onclick="Backend.getScrollOffset();"',
                ],
        ],
        'operations'        => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG'][$table]['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.gif',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG'][$table]['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif',
            ],
        ],
    ],

    // MetaPalettes
    'metapalettes' => [
        'default' => [
            'name'   => [
                'name',
                'type',
                'title',
            ],
            'config' => [
                'notification_new',
                'notification_onChange',
                'cssClass',
                'messageType',
                'locked',
                'increasesCount',
                'enableManualAssignment',
            ],
        ],
    ],

    // Fields
    'fields'       => [
        'id'                     => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp'                 => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'type'                   => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['type'],
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => $GLOBALS['FERIENPASS_STATUS'],
            'reference' => &$GLOBALS['TL_LANG']['MSC']['ferienpass.attendance-status'],
            'eval'      => [
                'tl_class' => 'w50',
                'unique'   => true,
            ],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'title'                  => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['title'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'maxlength' => 255,
                'tl_class'  => 'w50',
            ],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'increasesCount'         => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['increasesCount'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => [
                'tl_class' => 'w50',
            ],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'locked'                 => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['locked'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => [
                'tl_class' => 'w50',
            ],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'notification_new'       => [
            'label'            => &$GLOBALS['TL_LANG'][$table]['notification_new'],
            'exclude'          => true,
            'inputType'        => 'select',
            'options_callback' => ['Ferienpass\Helper\Dca', 'getNotificationChoices'],
            'eval'             => [
                'includeBlankOption' => true,
                'chosen'             => true,
                'tl_class'           => 'w50',
            ],
            'sql'              => "int(10) unsigned NOT NULL default '0'",
            'relation'         => ['type' => 'hasOne', 'table' => Notification::getTable()],
        ],
        'notification_onChange'  => [
            'label'            => &$GLOBALS['TL_LANG'][$table]['notification_onChange'],
            'exclude'          => true,
            'inputType'        => 'select',
            'options_callback' => ['Ferienpass\Helper\Dca', 'getNotificationChoices'],
            'eval'             => [
                'includeBlankOption' => true,
                'chosen'             => true,
                'tl_class'           => 'w50',
            ],
            'sql'              => "int(10) unsigned NOT NULL default '0'",
            'relation'         => [
                'type'  => 'hasOne',
                'table' => Notification::getTable(),
            ],
        ],
        'cssClass'               => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['cssClass'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'tl_class'  => 'w50',
                'mandatory' => true,
            ],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'messageType'            => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['messageType'],
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => Ferienpass\Helper\Message::getTypes(),
            'eval'      => [
                'mandatory' => true,
                'tl_class'  => 'w50',
            ],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'enableManualAssignment' => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['enableManualAssignment'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => [
                'tl_class' => 'w50',
            ],
            'sql'       => "char(1) NOT NULL default ''",
        ],

    ],
];
