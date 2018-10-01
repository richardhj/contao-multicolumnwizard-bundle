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

$GLOBALS['TL_DCA']['tl_ferienpass_code'] = [

    // Config
    'config'       => [
        'dataContainer' => 'General',
        'closed'        => true,
        'notEditable'   => true,
        'notDeletable'  => true,
        'sql'           => [
            'keys' => [
                'id'   => 'primary',
                'code' => 'unique'
            ]
        ]
    ],

    // DCA config
    'dca_config'   => [
        'data_provider' => [
            'default' => [
                'source' => 'tl_ferienpass_code'
            ],
        ],
    ],

    // List
    'list'         => [
        'sorting'           => [
            'mode'        => 1,
            'flag'        => 1,
            'fields'      => ['code'],
            'panelLayout' => 'limit',
        ],
        'label'             => [
            'fields' => [
                'code'
            ],
            'format' => '%s',
        ],
        'global_operations' => [
            'generate_codes' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_ferienpass_code']['generate_codes'],
                'href'       => 'key=generate',
                'class'      => '',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
            'back'           => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['backBT'],
                'href'       => 'mod=&table=',
                'class'      => 'header_back',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
        ],
        'operations'        => [
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_edition']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif',
            ],
        ],
    ],

    // MetaPalettes
    'metapalettes' => [
        'default' => [
            'code' => [
                'code',
            ],
        ],
    ],

    // Fields
    'fields'       => [
        'id'        => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment'
        ],
        'tstamp'    => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'activated' => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'att_id'    => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'item_id'   => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'code'      => [
            'sql' => "varchar(32) NOT NULL default ''"
        ],
    ]
];
