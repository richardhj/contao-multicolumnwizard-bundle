<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem;


global $container;
$table = Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem::getTable();


/** @noinspection PhpUndefinedMethodInspection */
$GLOBALS['TL_DCA'][$table] = [

    // Config
    'config'                => [
        'dataContainer'   => 'General',
        'notCreatable'    => true,
        'notDeletable'    => true,
        'onload_callback' => [
            ['Richardhj\ContaoFerienpassBundle\Helper\Dca', 'addDefaultApplicationSystems'],
        ],
        'sql'             => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    // List
    'list'                  => [
        'sorting'           => [
            'mode'        => 1,
            'fields'      => ['type'],
            'panelLayout' => 'limit',
        ],
        'label'             => [
            'fields'      => [
                'type',
                'title',
                'start',
                'stop',
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
            'edit'   => [
                'label' => &$GLOBALS['TL_LANG'][$table]['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.gif',
            ],
            'toggle' => [
                'label'          => &$GLOBALS['TL_LANG'][$table]['toggle'],
                'icon'           => 'visible.gif',
                'toggleProperty' => 'published',
            ],
            'show'   => [
                'label' => &$GLOBALS['TL_LANG'][$table]['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif',
            ],
        ],
    ],

    // MetaPalettes
    'metapalettes'          => [
        'default' => [
            'config'    => [
                'title',
                'type',
            ],
            'published' => [
                'published',
            ],
        ],
    ],

    // MetaSubPalettes
    'metasubpalettes'       => [
        'published' => [
            'start',
            'stop',
        ],
    ],

    // MetaSubSelectPalttes
    'metasubselectpalettes' => [
        'type' => [
            'firstcome' => [
                'maxApplicationsPerDay',
//                'value1_subfield2',
            ],
            'lot'       => [
//                'value2_subfield1',
            ],
        ],
    ],

    // Fields
    'fields'                => [
        'id'                    => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp'                => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'type'                  => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['type'],
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => ApplicationSystem::getApplicationSystemNames(),
            'reference' => &$GLOBALS['TL_LANG']['MSC']['ferienpass.attendance-status'],
            'eval'      => [
                'tl_class' => 'w50',
                'unique'   => true,
//                'disabled' => true,
//                'readonly' => true,
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
        'maxApplicationsPerDay' => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['maxApplicationsPerDay'],
            'inputType' => 'text',
            'eval'      => [
                'tl_class' => 'w50',
                'rgxp'     => 'numeric',
            ],
            'sql'       => "int(5) NOT NULL default '0'",
        ],
        'published'             => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['published'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => [
                'submitOnChange' => true,
                'doNotCopy'      => true,
            ],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'start'                 => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['start'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'rgxp'       => 'datim',
                'datepicker' => true,
                'tl_class'   => 'w50 wizard',
            ],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
        'stop'                  => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['stop'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'rgxp'       => 'datim',
                'datepicker' => true,
                'tl_class'   => 'w50 wizard',
            ],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
    ],
];
