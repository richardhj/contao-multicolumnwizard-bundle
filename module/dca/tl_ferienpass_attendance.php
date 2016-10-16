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
//        'closed'        => true,
        'notDeletable'  => true,
        'sql'           => [
            'keys' => [
                'id'                => 'primary',
                'offer,participant' => 'unique',
            ],
        ],
    ],

    'dca_config'   => [
        'data_provider'  =>
            [
                'default' =>
                    [
                        'source' => $table,
                    ],
//            'tl_metamodel_dca_sortgroup' =>
//                [
//                'source' => 'tl_metamodel_dca_sortgroup'
//                ],
//            'tl_metamodel_dcasetting'    =>
//                [
//                'source' => 'tl_metamodel_dcasetting'
//                ],
            ],
        'childCondition' => [
            [
                'from'   => $table,
                'to'     => Offer::getInstance()->getMetaModel()->getTableName(),
                'setOn'  => [
                    [
                        'to_field'   => 'id',
                        'from_field' => 'offer',
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
                'from'   => $table,
                'to'     => Participant::getInstance()->getMetaModel()->getTableName(),
                'setOn'  => [
                    [
                        'to_field'   => 'id',
                        'from_field' => 'participant',
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
            'mode'        => 0,
            'fields'      => [
                'tstamp',
                'offer',
                'participant',
                'status',
            ],
//            'flag'        => 0,
//            'filter'                => [['participant', \Input::get('participant')]],
            'panelLayout' => 'filter',
        ],
        'label'             =>
            [
                'fields'      => [
                    'tstamp',
                    'offer',
                    'participant',
                    'status',
                ],
                'showColumns' => true,
//                'format' => '%s <span class="tl_gray">[%s]</span>',
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
        'operations'        =>
            [
//                'edit'          =>
//                    [
//                        'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['edit'],
//                        'href'  => 'act=edit',
//                        'icon'  => 'edit.gif',
//                    ],
//                'copy'          =>
//                    [
//                        'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['copy'],
//                        'href'  => 'act=copy',
//                        'icon'  => 'copy.gif',
//                    ],
//                'delete'        =>
//                    [
//                        'label'      => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['delete'],
//                        'href'       => 'act=delete',
//                        'icon'       => 'delete.gif',
//                        'attributes' => 'onclick="if (!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\')) return false; Backend.getScrollOffset();"',
//                    ],
                'show'          =>
                    [
                        'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['show'],
                        'href'  => 'act=show',
                        'icon'  => 'show.gif',
                    ],
                'toggle_status' => [
                    'label'                => &$GLOBALS['TL_LANG'][$table]['toggle_status'],
                    'attributes'           => 'onclick="Backend.getScrollOffset();"',
                    'haste_ajax_operation' => [
                        'field'   => 'status',
                        'options' => [
                            [
                                'value' => '1',
                                'icon'  => 'assets/ferienpass/backend/img/equalizer.png',
                            ],
                            [
                                'value' => '2',
                                'icon'  => 'visible.gif',
                            ],
                        ],
                    ],
                ],
            ],
    ],

//    // Meta Palettes
    'metapalettes' => array
    (
        'default' => array
        (
            'title' => array
            (
                'offer',
                'participant',
                'status',
            ),
        ),
    ),

    // Fields
    'fields'       => [
        'id'          => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp'      => [
            'sql'  => "int(10) unsigned NOT NULL default '0'",
            'eval' => ['rgxp' => 'datim']
//            'flag' => 5,
        ],
        'offer'       => [
            'inputType' => 'tableLookup',
            'eval'      => [
                // The foreign table you want to search in
                'foreignTable'     => Offer::getInstance()->getMetaModel()->getTableName(),

                // Define "checkbox" for multi selects and "radio" for single selects
                'fieldType'        => 'radio',

                // A list of fields to be displayed in the table
                'listFields'       => [
                    Ferienpass\Model\Config::getInstance()->offer_attribute_name,
                    Ferienpass\Model\Config::getInstance()->offer_attribute_date_check_age,
                ],

//                // Custom labels to be displayed in the table header
//                'customLabels'        => ['Label 1', 'Label 2', 'Label 3'],

                // Fields that can be searched for the keyword
                'searchFields'     => [
                    Ferienpass\Model\Config::getInstance()->offer_attribute_name,
//                    'tl_my_superb_join_table.field1',
                ],

//                // Adds multiple left joins to the sql statement (optional)
//                'joins'            =>
//                    [
//                        // Defines the join table
//                        'tl_my_superb_join_table' =>
//                            [
//                                // Join type (e.g. INNER JOIN, LEFT JOIN, RIGHT JOIN)
//                                'type' => 'LEFT JOIN',
//
//                                // Key of the join table
//                                'jkey' => 'pid',
//
//                                // Key of the foreign table
//                                'fkey' => 'id',
//                            ],
//                    ],

                // Find every given keyword
                'matchAllKeywords' => true,

                // Exclude varbases if they have childs
                'sqlWhere'         => sprintf(
                    '%1$s.varbase=0 OR (%1$s.varbase=1 AND (SELECT COUNT(*) FROM %1$s c WHERE c.varbase=0 AND c.vargroup=%1$s.id)=0)',
                    Offer::getInstance()->getMetaModel()->getTableName()
                ),

//                // Custom ORDER BY - note that when you use "enableSorting" you cannot set this value!
//                'sqlOrderBy'       => 'someColumn',

//                // Adds a "GROUP BY" to the sql statement (optional)
//                'sqlGroupBy'       => 'tl_my_superb_join_table.fid',

//                // The search button label
//                'searchLabel'      => 'Search my table now!',

//                // Enables drag n drop sorting of chosen values
//                'enableSorting'    => true,
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
                // The foreign table you want to search in
                'foreignTable'     => Participant::getInstance()->getMetaModel()->getTableName(),

                // Define "checkbox" for multi selects and "radio" for single selects
                'fieldType'        => 'radio',

                // A list of fields to be displayed in the table
                'listFields'       => [
                    Ferienpass\Model\Config::getInstance()->participant_attribute_name,
                    Ferienpass\Model\Config::getInstance()->participant_attribute_dateofbirth,
//                    sprintf('CONCAT (%1$s.firstname, \' \', %1$s.lastname', \MemberModel::getTable()),
                    \MemberModel::getTable().'.firstname',
                    \MemberModel::getTable().'.lastname',

                ],

//                // Custom labels to be displayed in the table header
//                'customLabels'        => ['Label 1', 'Label 2', 'Label 3'],

                // Fields that can be searched for the keyword
                'searchFields'     => [
                    Ferienpass\Model\Config::getInstance()->participant_attribute_name,
//                    \MemberModel::getTable().'.*',
                ],

                // Adds multiple left joins to the sql statement (optional)
                'joins'            => [
                    \MemberModel::getTable() => [
                        'type' => 'INNER JOIN',
                        'jkey' => 'id',
                        'fkey' => Participant::getInstance()->getMetaModel()->getAttributeById(
                            Participant::getInstance()->getMetaModel()->get('owner_attribute')
                        )->getColName(),
                    ],
                ],

                // Find every given keyword
                'matchAllKeywords' => true,

//                // Exclude varbases if they have childs
//                'sqlWhere'         => sprintf('%1$s.varbase=0 OR (%1$s.varbase=1 AND (SELECT COUNT(*) FROM %1$s c WHERE c.varbase=0 AND c.vargroup=%1$s.id)=0)', Offer::getInstance()->getMetaModel()->getTableName()),

//                // Custom ORDER BY - note that when you use "enableSorting" you cannot set this value!
//                'sqlOrderBy'       => 'someColumn',

//                // Adds a "GROUP BY" to the sql statement (optional)
//                'sqlGroupBy'       => 'tl_my_superb_join_table.fid',

//                // The search button label
//                'searchLabel'      => 'Search my table now!',

//                // Enables drag n drop sorting of chosen values
//                'enableSorting'    => true,
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
//            'options'   => [1, 2],
            'reference' => AttendanceStatus::findAll()->fetchEach('name'),
            'sql'       => "int(10) unsigned NOT NULL default '0'",
            'relation'  => [
                'type'  => 'hasOne',
                'table' => AttendanceStatus::getTable(),
            ],
        ],
    ],
];
