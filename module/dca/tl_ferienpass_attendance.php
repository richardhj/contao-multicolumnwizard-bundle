<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

use Ferienpass\Model\AttendanceStatus;
use Ferienpass\Model\Offer;
use Ferienpass\Model\Participant;
use MetaModels\IItem;


$table = Ferienpass\Model\Attendance::getTable();


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
            Offer::getInstance()->getTableName()       => [
                'source' => Offer::getInstance()->getTableName(),
            ],
            Participant::getInstance()->getTableName() => [
                'source' => Participant::getInstance()->getTableName(),
            ],
        ],
        'child_list'     => [
            Offer::getInstance()->getTableName() => [
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
                'from'   => Offer::getInstance()->getTableName(),
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
                'from'   => Participant::getInstance()->getTableName(),
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
                'sorting'
////                'tstamp',
////                'offer',
////                'participant',
////                'status',
            ],
            'headerFields' => [
                'id',
//                Offer::getInstance()->getMetaModel()->getAttribute(Ferienpass\Model\Config::getInstance()->offer_attribute_name)->getColName(),
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
            'cut'  => [
                'label'      => &$GLOBALS['TL_LANG'][$table]['cut'],
                'href'       => 'act=paste&amp;mode=cut',
                'icon'       => 'cut.gif',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
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
//                            'icon'  => 'assets/ferienpass/backend/img/equalizer.png',
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
                'foreignTable'     => Offer::getInstance()->getTableName(),
                'fieldType'        => 'radio',
                'listFields'       => [
                    Ferienpass\Model\Config::getInstance()->offer_attribute_name,
//                    Ferienpass\Model\Config::getInstance()->offer_attribute_date,
                ],
                'searchFields'     => [
                    Ferienpass\Model\Config::getInstance()->offer_attribute_name,
                ],
                'matchAllKeywords' => true,
                // Exclude varbases if they have children
                'sqlWhere'         => sprintf(
                    '%1$s.varbase=0 OR (%1$s.varbase=1 AND (SELECT COUNT(*) FROM %1$s c WHERE c.varbase=0 AND c.vargroup=%1$s.id)=0)',
                    Offer::getInstance()->getTableName()
                ),
            ],
            'sql'       => "int(10) unsigned NOT NULL default '0'",
            'reference' => array_reduce(
                iterator_to_array(Offer::getInstance()->findAll()),
                function (array $carry, IItem $item) {
                    $carry[$item->get('id')] = $item->get(
                        Ferienpass\Model\Config::getInstance()->offer_attribute_name
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
                'mandatory'    => true,
                'foreignTable' => Participant::getInstance()->getTableName(),
                'fieldType'    => 'radio',
                'listFields'   => [
                    Ferienpass\Model\Config::getInstance()->participant_attribute_name,
                    Ferienpass\Model\Config::getInstance()->participant_attribute_dateofbirth,
                    \MemberModel::getTable().'.firstname',
                    \MemberModel::getTable().'.lastname',

                ],
                'searchFields' => [
                    Ferienpass\Model\Config::getInstance()->participant_attribute_name,
                ],
                'joins'            => [
                    \MemberModel::getTable() => [
                        'type' => 'INNER JOIN',
                        'jkey' => 'id',
                        'fkey' => Participant::getInstance()->getMetaModel()->getAttributeById(
                            Participant::getInstance()->getMetaModel()->get('owner_attribute')
                        )->getColName(),
                    ],
                ],
                'matchAllKeywords' => true,
            ],
            'sql'       => "int(10) unsigned NOT NULL default '0'",
            'reference' => array_reduce(
                iterator_to_array(Participant::getInstance()->findAll()),
                function (array $carry, IItem $item) {
                    $carry[$item->get('id')] = $item->get(
                        Ferienpass\Model\Config::getInstance()->participant_attribute_name
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
            'reference' => array_reduce(
                iterator_to_array(AttendanceStatus::findAll()),
                function (array $carry, AttendanceStatus $status) {
                    $carry[$status->id] = $GLOBALS['TL_LANG']['MSC']['ferienpass.attendance-status'][$status->type];

                    return $carry;
                },
                []
            ),
            'sql'       => "int(10) unsigned NOT NULL default '0'",
            'relation'  => [
                'type'  => 'hasOne',
                'table' => AttendanceStatus::getTable(),
            ],
        ],
    ],
];
