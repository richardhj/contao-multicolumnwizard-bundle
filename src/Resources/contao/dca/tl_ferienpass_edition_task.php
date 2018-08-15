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

$GLOBALS['TL_DCA']['tl_ferienpass_edition_task'] = [

    // Config
    'config'                => [
        'dataContainer' => 'General',
        'ptable'        => 'tl_ferienpass_edition',
        'sql'           => [
            'keys' => [
                'id'  => 'primary',
                'pid' => 'index'
            ],
        ],
    ],

    // DCA config
    'dca_config'            => [
        'data_provider'  => [
            'parent' => [
                'source' => 'tl_ferienpass_edition'
            ],
        ],
        'childCondition' => [
            [
                'from'    => 'tl_ferienpass_edition',
                'to'      => 'tl_ferienpass_edition_task',
                'setOn'   => [
                    [
                        'to_field'   => 'pid',
                        'from_field' => 'id',
                    ],
                ],
                'filter'  => [
                    [
                        'local'     => 'pid',
                        'remote'    => 'id',
                        'operation' => '=',
                    ],
                ],
                'inverse' => [
                    [
                        'local'     => 'pid',
                        'remote'    => 'id',
                        'operation' => '=',
                    ],
                ]
            ],
        ],
    ],

    // List
    'list'                  => [
        'sorting'           => [
            'mode'        => 2,
            'fields'      => [],
            'flag'        => 1,
            'panelLayout' => 'sort,search;limit',
        ],
        'label'             => [
            'fields'      => [
                'type',
                'period_start',
                'period_stop'
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
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_edition_task']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.gif',
            ],
            'delete' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_ferienpass_edition_task']['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.gif',
                'attributes' => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm']
                                . '\')) return false; Backend.getScrollOffset();"',
            ],
            'show'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_edition_task']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif',
            ],
        ],
    ],

    // MetaPalettes
    'metapalettes'          => [
        'default' => [
            'title'  => [
                'type',
            ],
            'period' => [
                'period_start',
                'period_stop',
            ],
            'chart'  => [
                'color',
                'description'
            ]
        ],
    ],

    // MetaSubSelectPalttes
    'metasubselectpalettes' => [
        'type' => [
            'custom'             => [
                'title',
            ],
            'application_system' => [
                'application_system',
                //condition
            ],
        ],
    ],

    // Fields
    'fields'                => [
        'id'                 => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid'                => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'tstamp'             => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'sorting'            => [
            'label'   => &$GLOBALS['TL_LANG']['tl_ferienpass_edition_task']['sorting'],
            'sorting' => true,
            'flag'    => 11,
            'sql'     => "int(10) unsigned NOT NULL default '0'",
        ],
        'type'               => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_edition_task']['type'],
            'reference' => &$GLOBALS['TL_LANG']['tl_ferienpass_edition_task']['type_options'],
            'inputType' => 'select',
            'options'   => [
                'holiday',
                'host_editing_stage',
                'application_system',
                'custom',
            ],
            'eval'      => [
                'mandatory'          => true,
                'submitOnChange'     => true,
                'includeBlankOption' => true
            ],
            'sql'       => "varchar(64) NOT NULL default ''"
        ],
        'title'              => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_edition_task']['title'],
            'inputType' => 'text',
            'eval'      => [
                'mandatory' => true
            ],
            'sql'       => "varchar(255) NOT NULL default ''"
        ],
        'application_system' => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_edition_task']['application_system'],
            'inputType' => 'select',
            'eval'      => [
                'mandatory'          => true,
                'includeBlankOption' => true
            ],
            'sql'       => "varchar(64) NOT NULL default ''"
        ],
        'period_start'       => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_edition_task']['period_start'],
            'exclude'   => true,
            'inputType' => 'text',
            'sorting'   => true,
            'flag'      => 9,
            'eval'      => [
                'rgxp'       => 'datim',
                'datepicker' => true,
                'tl_class'   => 'w50 wizard',
            ],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
        'period_stop'        => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_edition_task']['period_stop'],
            'exclude'   => true,
            'inputType' => 'text',
            'sorting'   => true,
            'flag'      => 9,
            'eval'      => [
                'rgxp'       => 'datim',
                'datepicker' => true,
                'tl_class'   => 'w50 wizard',
            ],
            'sql'       => "varchar(10) NOT NULL default ''",
        ],
        'color'              => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_edition_task']['color'],
            'inputType' => 'text',
            'eval'      => [
                'colorpicker'    => true,
                'isHexColor'     => true,
                'decodeEntities' => true,
                'tl_class'       => 'w50 wizard'
            ],
            'sql'       => "char(6) NOT NULL default ''"
        ],
        'description'        => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_edition_task']['description'],
            'inputType' => 'textarea',
            'eval'      => [
                'tl_class' => 'clr long'
            ],
            'sql'       => 'text NULL'
        ],
    ],
];
