<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE
 */

$GLOBALS['TL_DCA']['tl_ferienpass_applicationsystem'] = [

    // Config
    'config'                => [
        'dataContainer'   => 'General',
        'notCreatable'    => true,
        'notDeletable'    => true,
        'sql'             => [
            'keys' => [
                'id'   => 'primary',
                'type' => 'unique'
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
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_applicationsystem']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.gif',
            ],
            'toggle' => [
                'label'          => &$GLOBALS['TL_LANG']['tl_ferienpass_applicationsystem']['toggle'],
                'icon'           => 'visible.gif',
                'toggleProperty' => 'published',
            ],
            'show'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_applicationsystem']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif',
            ],
        ],
    ],

    // MetaPalettes
    'metapalettes'          => [
        'default' => [
            'config'    => [
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
            ],
            'lot'       => [
            ],
        ],
    ],

    // Fields
    'fields'                => [
        'id'                    => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp'                => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'type'                  => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_applicationsystem']['type'],
            'exclude'   => true,
            'inputType' => 'justtextoption',
            'options'   => [
                'lot',
                'firstcome'
            ],
            'reference' => &$GLOBALS['TL_LANG']['MSC']['application_system'],
            'eval'      => [
                'tl_class' => 'w50',
            ],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'maxApplicationsPerDay' => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_applicationsystem']['maxApplicationsPerDay'],
            'inputType' => 'text',
            'eval'      => [
                'tl_class' => 'w50',
                'rgxp'     => 'numeric',
            ],
            'sql'       => "int(5) NOT NULL default '0'",
        ],
        'published'             => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_applicationsystem']['published'],
            'exclude'   => true,
            'inputType' => 'checkbox',
            'eval'      => [
                'submitOnChange' => true,
                'doNotCopy'      => true,
            ],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'start'                 => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_applicationsystem']['start'],
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
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_applicationsystem']['stop'],
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
