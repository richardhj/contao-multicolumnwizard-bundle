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

use Contao\MemberModel;
use Contao\System;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;
use MetaModels\IItem;
use MetaModels\Items;


$table = Richardhj\ContaoFerienpassBundle\Model\Attendance::getTable();


/**
 * DCA
 */
$GLOBALS['TL_DCA'][$table] = [

    // Config
    'config' => [
        'dataContainer' => 'General',
        'notDeletable'  => true,
        'sql'           => [
            'keys' => [
                'id'                => 'primary',
                'offer,participant' => 'unique',
            ],
        ],
    ],

    'dca_config'   => [
        'data_provider'  => [
//            'parent'  => [
//                'source' => Offer::getInstance()->getTable(),
//            ],
            'default'                                  => [
                'source' => $table,
            ],
            'mm_ferienpass'       => [
                'source' => 'mm_ferienpass',
            ],
            'mm_participant' => [
                'source' => 'mm_participant',
            ],
        ],
        'child_list'     => [
            'mm_ferienpass' => [
                'fields' => ['tstamp'],
            ],
            $table                               => [
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
                'to'     => $table,
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
                'to'     => $table,
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
//            'flag'                  => 6,
//            'mode'         => 0,
            'fields'       => [
//                'sorting'
////                'tstamp',
////                'offer',
////                'participant',
////                'status',
            ],
            'headerFields' => [
                'id',
//                Offer::getInstance()->getMetaModel()->getAttribute(Richardhj\ContaoFerienpassBundle\Model\Config::getInstance()->offer_attribute_name)->getColName(),
            ],
            'panelLayout'  => 'filter',
        ],
        'label'             =>
            [
                'showColumns' => true,
            ],
        'global_operations' =>
            [
                'back' =>
                    [
                        'label'      => &$GLOBALS['TL_LANG']['MSC']['backBT'],
                        'href'       => 'mod=&table=',
                        'class'      => 'header_back',
                        'attributes' => 'onclick="Backend.getScrollOffset();"',
                    ],
                'all'  =>
                    [
                        'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                        'href'       => 'act=select',
                        'class'      => 'header_edit_all',
                        'attributes' => 'onclick="Backend.getScrollOffset();"',
                    ],
            ],
        'operations'        => [
//            'cut'  => [
//                'label'      => &$GLOBALS['TL_LANG'][$table]['cut'],
//                'href'       => 'act=paste&amp;mode=cut',
//                'icon'       => 'cut.gif',
//                'attributes' => 'onclick="Backend.getScrollOffset()"',
//            ],
            'show' =>
                [
                    'label' => &$GLOBALS['TL_LANG'][$table]['show'],
                    'href'  => 'act=show',
                    'icon'  => 'show.gif',
                ],
//            'toggle_status' => [
//                'label'                => &$GLOBALS['TL_LANG'][$table]['toggle_status'],
//                'attributes'           => 'onclick="Backend.getScrollOffset();"',
//                'haste_ajax_operation' => [
//                    'field'   => 'status',
//                    'options' => [
//                        [
//                            'value' => '1',
//                            'icon'  => 'assets/ferienpass/core/img/equalizer.png',
//                        ],
//                        [
//                            'value' => '2',
//                            'icon'  => 'visible.gif',
//                        ],
//                    ],
//                ],
//            ],
        ],
    ],

//    // Meta Palettes
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
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp'      => [
            'label' => &$GLOBALS['TL_LANG'][$table]['tstamp'],
            'eval'  => ['rgxp' => 'datim'],
            'sql'   => "int(10) unsigned NOT NULL default '0'",
//            'flag' => 5,
        ],
        'sorting'     => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'created'     => [
            'label' => &$GLOBALS['TL_LANG'][$table]['created'],
            'eval'  => ['rgxp' => 'datim'],
            'sql'   => "int(10) unsigned NOT NULL default '0'",
        ],
        'offer'       => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['offer'],
            'inputType' => 'tableLookup',
            'eval'      => [
                'mandatory'        => true,
                'foreignTable'     => 'mm_ferienpass',
                'fieldType'        => 'radio',
                'listFields'       => [
                    'name'
                ],
                'searchFields'     => [
                    'name',
                ],
                'matchAllKeywords' => true,
                // Exclude varbases if they have children
                'sqlWhere'         => sprintf(
                    '%1$s.varbase=0 OR (%1$s.varbase=1 AND (SELECT COUNT(*) FROM %1$s c WHERE c.varbase=0 AND c.vargroup=%1$s.id)=0)',
                    'mm_ferienpass'
                ),
            ],
            'sql'       => "int(10) unsigned NOT NULL default '0'",
            'reference' => array_reduce(
                iterator_to_array((System::getContainer()->get('richardhj.ferienpass.model.offer')->findAll() ?: new Items([]))),
                function (array $carry, IItem $item) {
                    $carry[$item->get('id')] = $item->get(
                        'name'
                    );

                    return $carry;
                },
                []
            ),
            'filter'    => true,
        ],
        'participant' => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['participant'],
            'inputType' => 'tableLookup',
            'eval'      => [
                'mandatory'        => true,
                'foreignTable'     => 'mm_participant',
                'fieldType'        => 'radio',
                'listFields'       => [
                    'name',
//                    'dateOfBirth',
                    MemberModel::getTable().'.firstname',
                    MemberModel::getTable().'.lastname',

                ],
                'customLabels' => [
                    'Name des Teilnehmers',
//                    'Geburtsdatum',
                    'Vorname Eltern',
                    'Nachname Eltern',
                ],
                'searchFields' => [
                    'name',
                    MemberModel::getTable().'.firstname',
                    MemberModel::getTable().'.lastname',
                ],
                'joins'            => [
                    MemberModel::getTable() => [
                        'type' => 'INNER JOIN',
                        'jkey' => 'id',
                        'fkey' => 'pmember',
                    ],
                ],
                'matchAllKeywords' => true,
            ],
            'sql'       => "int(10) unsigned NOT NULL default '0'",
            'reference' => array_reduce(
                iterator_to_array((System::getContainer()->get('richardhj.ferienpass.model.participant')->findAll() ?: new Items([]))),
                function (array $carry, IItem $item) {
                    $carry[$item->get('id')] = $item->get(
                        'name'
                    );

                    return $carry;
                },
                []
            ),
            'filter'    => true,
        ],
        'status'      => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['status'],
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
                'table' => AttendanceStatus::getTable(),
            ],
        ],
    ],
];
