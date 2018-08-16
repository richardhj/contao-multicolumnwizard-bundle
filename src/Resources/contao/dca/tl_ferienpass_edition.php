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
    'config'       => [
        'dataContainer' => 'General',
        'ctable'        => ['tl_ferienpass_edition_task'],
        'sql'           => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    // DCA config
    'dca_config'   => [
        'data_provider'  => [
            'default' => [
                'source' => 'tl_ferienpass_edition'
            ],

            'tl_ferienpass_edition_task' => [
                'source' => 'tl_ferienpass_edition_task'
            ],
        ],
        'childCondition' => [
            [
                'from'    => 'tl_ferienpass_edition',
                'to'      => 'tl_ferienpass_edition_task',
                'setOn'   =>
                    [
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
    'list'         => [
        'sorting'           => [
            'mode'        => 1,
            'flag'        => 1,
            'fields'      => ['title'],
            'panelLayout' => 'limit',
        ],
        'label'             => [
            'fields' => [
                'title'
            ],
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
            'edit'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_edition']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.gif',
            ],
            'tasks'  => [
                'label'   => &$GLOBALS['TL_LANG']['tl_ferienpass_edition']['tasks'],
                'href'    => 'table=tl_ferienpass_edition_task',
                'icon'    => 'bundles/richardhjcontaoferienpass/img/tasks.svg',
                'idparam' => 'pid'
            ],
            'delete' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_ferienpass_edition']['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.gif',
                'attributes' => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm']
                                . '\')) return false; Backend.getScrollOffset();"',
            ],
            'show'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_edition']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif',
            ],
        ],
    ],

    // MetaPalettes
    'metapalettes' => [
        'default' => [
            'title' => [
                'title',
            ],
        ],
    ],

    // Fields
    'fields'       => [
        'id'     => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'title'  => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_edition']['title'],
            'inputType' => 'text',
            'eval'      => [
                'mandatory' => true
            ],
            'sql'       => "varchar(255) NOT NULL default ''"
        ],
    ],
];
