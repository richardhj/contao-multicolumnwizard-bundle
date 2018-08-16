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
        'dataContainer' => 'General',
        'notCreatable'  => true,
        'notDeletable'  => true,
        'sql'           => [
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
            'flag'        => 1,
            'fields'      => ['type'],
            'panelLayout' => 'limit',
        ],
        'label'             => [
            'fields' => ['type'],
            'format' => '%s',
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
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_applicationsystem']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.gif',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_applicationsystem']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif',
            ],
        ],
    ],

    // MetaPalettes
    'metapalettes'          => [
        'default' => [
            'config' => [
                'type',
            ],
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
    ],
];
