<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

use Contao\System;
use NotificationCenter\Model\Notification;
use Richardhj\ContaoFerienpassBundle\Helper\Dca;

$table = Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus::getTable();


$GLOBALS['TL_DCA'][$table] = [

    // Config
    'config'       => [
        'dataContainer'   => 'General',
        'notCreatable'    => true,
        'notDeletable'    => true,
        'onload_callback' => [
            [Dca::class, 'addDefaultStatus'],
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
                'type',
                'title',
            ],
            'config' => [
                'notification_new',
                'notification_onChange',
                'messageType',
            ],
        ],
    ],

    // Fields
    'fields'       => [
        'id'                    => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp'                => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'type'                  => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['type'],
            'exclude'   => true,
            'inputType' => 'justtextoption',
            'options'   => ['confirmed', 'waitlisted', 'waiting', 'error'],
            'reference' => &$GLOBALS['TL_LANG']['MSC']['attendance_status'],
            'eval'      => [
                'tl_class' => 'w50',
                'unique'   => true,
            ],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'title'                 => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['title'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'maxlength' => 255,
                'tl_class'  => 'w50',
            ],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'notification_new'      => [
            'label'            => &$GLOBALS['TL_LANG'][$table]['notification_new'],
            'exclude'          => true,
            'inputType'        => 'select',
            'options_callback' => [Dca::class, 'getNotificationChoices'],
            'eval'             => [
                'includeBlankOption' => true,
                'chosen'             => true,
                'tl_class'           => 'w50',
            ],
            'sql'              => "int(10) unsigned NOT NULL default '0'",
            'relation'         => ['type' => 'hasOne', 'table' => Notification::getTable()],
        ],
        'notification_onChange' => [
            'label'            => &$GLOBALS['TL_LANG'][$table]['notification_onChange'],
            'exclude'          => true,
            'inputType'        => 'select',
            'options_callback' => [Dca::class, 'getNotificationChoices'],
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
        'messageType'           => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['messageType'],
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => Richardhj\ContaoFerienpassBundle\Helper\Message::getTypes(),
            'eval'      => [
                'mandatory' => true,
                'tl_class'  => 'w50',
            ],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
    ],
];
