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
//                'source' => Offer::getInstance()->getMetaModel()->getTableName(),
//            ],
            'default'                                                  => [
                'source' => $table,
            ],
            Offer::getInstance()->getMetaModel()->getTableName()       => [
                'source' => Offer::getInstance()->getMetaModel()->getTableName(),
            ],
            Participant::getInstance()->getMetaModel()->getTableName() => [
                'source' => Participant::getInstance()->getMetaModel()->getTableName(),
            ],
        ],
        'child_list'     => [
            Offer::getInstance()->getMetaModel()->getTableName() => [
                'fields' => ['tstamp'],
            ],
            $table                                               => [
                'fields' => [
                    'tstamp',
                    'offer',
                    'participant',
                    'status',
                ],
            ],
        ],
        'childCondition' => [
            [
                'from'   => Offer::getInstance()->getMetaModel()->getTableName(),
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
                'from'   => Participant::getInstance()->getMetaModel()->getTableName(),
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
//            'mode'         => 1,
//            'flag'                  => 6,
//            'mode'         => 0,
//            'fields'       => [
//                'sorting'
////                'tstamp',
////                'offer',
////                'participant',
////                'status',
//            ],
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
            'show' =>
                [
                    'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['show'],
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
            'title' => [
                'offer',
                'participant',
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
            'sql'  => "int(10) unsigned NOT NULL default '0'",
            'eval' => ['rgxp' => 'datim'],
//            'flag' => 5,
        ],
        'sorting'     => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'offer'       => [
            'inputType' => 'tableLookup',
            'eval'      => [
                'foreignTable'     => Offer::getInstance()->getMetaModel()->getTableName(),
                'fieldType'        => 'radio',
                'listFields'       => [
                    Ferienpass\Model\Config::getInstance()->offer_attribute_name,
                    Ferienpass\Model\Config::getInstance()->offer_attribute_date_check_age,
                ],
                'searchFields'     => [
                    Ferienpass\Model\Config::getInstance()->offer_attribute_name,
                ],
                'matchAllKeywords' => true,
                // Exclude varbases if they have children
                'sqlWhere'         => sprintf(
                    '%1$s.varbase=0 OR (%1$s.varbase=1 AND (SELECT COUNT(*) FROM %1$s c WHERE c.varbase=0 AND c.vargroup=%1$s.id)=0)',
                    Offer::getInstance()->getMetaModel()->getTableName()
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
            'inputType' => 'tableLookup',
            'eval'      => [
                'foreignTable'     => Participant::getInstance()->getMetaModel()->getTableName(),
                'fieldType'        => 'radio',
                'listFields'       => [
                    Ferienpass\Model\Config::getInstance()->participant_attribute_name,
                    Ferienpass\Model\Config::getInstance()->participant_attribute_dateofbirth,
                    \MemberModel::getTable().'.firstname',
                    \MemberModel::getTable().'.lastname',

                ],
                'searchFields'     => [
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
            'reference' => AttendanceStatus::findAll()->fetchEach('name'),
            'sql'       => "int(10) unsigned NOT NULL default '0'",
            'relation'  => [
                'type'  => 'hasOne',
                'table' => AttendanceStatus::getTable(),
            ],
        ],
    ],
];
