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

use Contao\System;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;
use MetaModels\IItem;
use MetaModels\Items;


$GLOBALS['TL_DCA']['tl_ferienpass_attendance'] = [

    // Config
    'config' => [
        'dataContainer' => 'General',
        'sql'           => [
            'keys' => [
                'id'                => 'primary',
                'offer,participant' => 'unique',
            ],
        ],
    ],

    'dca_config'   => [
        'data_provider'  => [
            'default'        => [
                'source' => 'tl_ferienpass_attendance',
            ],
            'mm_ferienpass'  => [
                'source' => 'mm_ferienpass',
            ],
            'mm_participant' => [
                'source' => 'mm_participant',
            ],
        ],
        'child_list'     => [
            'mm_ferienpass'            => [
                'fields' => ['tstamp'],
            ],
            'tl_ferienpass_attendance' => [
                'fields' => [
                    'created',
                    'offer',
                    'participant',
                    'status',
                ],
            ],
        ],
        'childCondition' => [
            [
                'from'   => 'mm_ferienpass',
                'to'     => 'tl_ferienpass_attendance',
                'setOn'  => [
                    [
                        'to_field'   => 'offer',
                        'from_field' => 'id',
                    ],
                ],
                'filter' => [
                    [
                        'local'     => 'offer',
                        'remote'    => 'id',
                        'operation' => '=',
                    ],
                ],
            ],
            [
                'from'   => 'mm_participant',
                'to'     => 'tl_ferienpass_attendance',
                'setOn'  => [
                    [
                        'to_field'   => 'participant',
                        'from_field' => 'id',
                    ],
                ],
                'filter' => [
                    [
                        'local'     => 'participant',
                        'remote'    => 'id',
                        'operation' => '=',
                    ],
                ],
            ],
        ],
    ],

    // List
    'list'         => [
        'sorting'           => [
            'mode'         => 1,
            'fields'       => [],
            'headerFields' => [
                'id',
            ],
            'panelLayout'  => 'filter',
        ],
        'label'             => [
            'showColumns' => true,
        ],
        'global_operations' => [
            'back' => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['backBT'],
                'href'       => 'mod=&table=',
                'class'      => 'header_back',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
            'all'  => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
        ],
        'operations'        => [
            'edit'   => [
                'label'      => &$GLOBALS['TL_LANG']['tl_ferienpass_attendance']['edit'],
                'href'       => 'act=edit',
                'icon'       => 'edit.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
            'delete' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_ferienpass_attendance']['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => sprintf(
                    'onclick="if (!confirm(\'%s\')) return false; Backend.getScrollOffset();"',
                    $GLOBALS['TL_LANG']['MSC']['deleteConfirm']
                )
            ],
            'show'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_attendance']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.svg',
            ],
        ],
    ],

    // Meta Palettes
    'metapalettes' => [
        'default' => [
            'offer'       => [
                'offer',
            ],
            'participant' => [
                'participant',
            ],
            'status'      => [
                'status',
            ],
        ],
    ],

    // Fields
    'fields'       => [
        'id'          => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp'      => [
            'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_attendance']['tstamp'],
            'eval'  => [
                'rgxp' => 'datim'
            ],
            'sql'   => "int(10) unsigned NOT NULL default '0'",
        ],
        'sorting'     => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'created'     => [
            'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_attendance']['created'],
            'eval'  => ['rgxp' => 'datim'],
            'sql'   => "int(10) unsigned NOT NULL default '0'",
        ],
        'offer'       => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_attendance']['offer'],
            'inputType' => 'tableLookup',
            'eval'      => [
                'mandatory'        => true,
                'foreignTable'     => 'mm_ferienpass',
                'fieldType'        => 'radio',
                'listFields'       => [
                    'name',
                    'tl_metamodel_offer_date.start'
                ],
                'searchFields'     => [
                    'name',
                    'date'
                ],
                'matchAllKeywords' => true,
                'joins'            => [
                    'tl_metamodel_offer_date' => [
                        'type' => 'INNER JOIN',
                        'fkey' => 'id',
                        'jkey' => 'item_id'
                    ]
                ],
            ],
            'sql'       => "int(10) unsigned NOT NULL default '0'",
            'reference' => array_reduce(
                iterator_to_array(
                    (System::getContainer()->get('richardhj.ferienpass.model.offer')->findAll() ?: new Items([]))
                ),
                function (array $carry, IItem $item) {
                    $carry[$item->get('id')] = $item->get('name');

                    return $carry;
                },
                []
            ),
            'filter'    => true,
        ],
        'participant' => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_attendance']['participant'],
            'inputType' => 'tableLookup',
            'eval'      => [
                'mandatory'        => true,
                'foreignTable'     => 'mm_participant',
                'fieldType'        => 'radio',
                'listFields'       => [
                    'name',
                    'tl_member.firstname',
                    'tl_member.lastname',

                ],
                'customLabels'     => [
                    'Name Teilnehmer_in',
                    'Vorname Eltern',
                    'Nachname Eltern',
                ],
                'searchFields'     => [
                    'name',
                    'tl_member.firstname',
                    'tl_member.lastname',
                ],
                'joins'            => [
                    'tl_member' => [
                        'type' => 'INNER JOIN',
                        'jkey' => 'id',
                        'fkey' => 'pmember',
                    ],
                ],
                'matchAllKeywords' => true,
            ],
            'sql'       => "int(10) unsigned NOT NULL default '0'",
            'reference' => array_reduce(
                iterator_to_array(
                    (System::getContainer()->get('richardhj.ferienpass.model.participant')->findAll() ?: new Items([]))
                ),
                function (array $carry, IItem $item) {
                    $carry[$item->get('id')] = $item->get('name');

                    return $carry;
                },
                []
            ),
            'filter'    => true,
        ],
        'status'      => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_attendance']['status'],
            'inputType' => 'select',
            'options'   => array_reduce(
                iterator_to_array(AttendanceStatus::findAll()),
                function (array $carry, AttendanceStatus $status) {
                    $carry[$status->id] = $GLOBALS['TL_LANG']['MSC']['ferienpass.attendance-status'][$status->type];

                    return $carry;
                },
                []
            ),
            'eval'      => [
                'includeBlankOption' => true,
            ],
            'sql'       => "int(10) unsigned NOT NULL default '0'",
            'relation'  => [
                'type'  => 'hasOne',
                'table' => 'tl_ferienpass_attendancestatus',
            ],
        ],
    ],
];
