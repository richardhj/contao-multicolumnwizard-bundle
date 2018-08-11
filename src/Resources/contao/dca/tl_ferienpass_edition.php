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

$GLOBALS['TL_DCA']['tl_ferienpass_edition'] = [

    // Config
    'config'                => [
        'dataContainer' => 'General',
        'sql'           => [
            'keys' => [
                'id'   => 'primary',
            ],
        ],
    ],

    // List
    'list'                  => [
        'sorting'           => [
            'mode'        => 1,
            'fields'      => ['title'],
            'panelLayout' => 'limit',
        ],
        'label'             => [
            'fields'      => [
                'title',
                'holiday_begin',
                'holiday_end',
            ],
            'showColumns' => true,
        ],
        'global_operations' => [
            'back' => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['backBT'],
                'href'       => 'mod=&table=',
                'class'      => 'header_back',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
        ],
        'operations'        => [
            'edit'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_edition']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.gif',
            ],
            'toggle' => [
                'label'          => &$GLOBALS['TL_LANG']['tl_ferienpass_edition']['toggle'],
                'icon'           => 'visible.gif',
                'toggleProperty' => 'published',
            ],
            'show'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_edition']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif',
            ],
        ],
    ],

    // MetaPalettes
    'metapalettes'          => [
        'default' => [
            'title'   => [
                'title',
            ],
            'periods' => [
                'holiday_begin',
                'holiday_end',
                'host_edit_end'
            ],
        ],
    ],

    // MetaSubPalettes
    'metasubpalettes'       => [
//        'published' => [
//            'start',
//            'stop',
//        ],
    ],

    // MetaSubSelectPalttes
    'metasubselectpalettes' => [
//        'type' => [
//            'firstcome' => [
//                'maxApplicationsPerDay',
//            ],
//            'lot'       => [
//            ],
//        ],
    ],

    // Fields
    'fields'                => [
        'id'            => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp'        => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'title'         => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_edition']['title'],
            'inputType' => 'text',
            'eval'      => [
                'mandatory' => true
            ],
            'sql'       => "varchar(255) NOT NULL default ''"
        ],
        'holiday_begin' => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_edition']['holiday_begin'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'rgxp'       => 'date',
                'datepicker' => true,
                'tl_class'   => 'w50 wizard',
            ],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
        'holiday_end'   => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_edition']['holiday_end'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'rgxp'       => 'date',
                'datepicker' => true,
                'tl_class'   => 'w50 wizard',
            ],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
        'host_edit_end' => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_edition']['host_edit_end'],
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
