<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


$table = Ferienpass\Model\DataProcessing::getTable();


$GLOBALS['TL_DCA'][$table] = [
    // Config
    'config'                => [
        'dataContainer' => 'General',
        'sql'           =>
            [
                'keys' => [
                    'id' => 'primary',
                ],
            ],
    ],

    // List
    'list'                  => [
        'sorting'           => [
            'mode'        => 1,
            'fields'      => ['name'],
            'flag'        => 1,
            'panelLayout' => 'filter;search,limit',
        ],
        'label'             => [
            'fields' => [
                'name',
                'filesystem',
            ],
            'format' => '%s <span class="tl_gray">[%s]</span>',
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
                'label' => &$GLOBALS['TL_LANG'][$table]['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.gif',
            ],
            'copy'   => [
                'label' => &$GLOBALS['TL_LANG'][$table]['copy'],
                'href'  => 'act=copy',
                'icon'  => 'copy.gif',
            ],
            'delete' => [
                'label'      => &$GLOBALS['TL_LANG'][$table]['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.gif',
                'attributes' => 'onclick="if (!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\')) return false; Backend.getScrollOffset();"',
            ],
            'show'   => [
                'label' => &$GLOBALS['TL_LANG'][$table]['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif',
            ],
        ],
    ],

    // Meta Palettes
    'metapalettes'          => [
        'default' => [
            'title'      => [
                'name',
            ],
            'processing' => [
                'type',
                'scope',
                'filesystem',
            ],
        ],
    ],
    // Meta SubSelect Palettes
    'metasubselectpalettes' => [
        'type'       => [
            'xml'  => [
                'metamodel_view',
                'combine_variants',
            ],
            'ical' => [
                'ical_fields',
            ],
        ],
        'scope'      => [
            'single' => [],
            'full'   => [
                'offer_image_path',
                'host_logo_path',
            ],
        ],
        'filesystem' => [
            'local'         => [
                'export_file_name',
                'path_prefix',
                'sync',
            ],
            'sendToBrowser' => [
                'export_file_name',
            ],
            'dropbox'       => [
                'dropbox_access_token',
                'path_prefix',
                'sync',
            ],
        ],
    ],

    // Fields
    'fields'                => [
        'id'                   => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp'               => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'name'                 => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['name'],
            'inputType' => 'text',
            'eval'      => [
                'mandatory' => true,
                'maxlength' => 255,
                'tl_class'  => 'w50',
            ],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'type'                 => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['type'],
            'inputType' => 'select',
            'default'   => 'xml',
            'options'   => [
                'xml',
                'ical',
            ],
            'reference' => &$GLOBALS['TL_LANG'][$table]['type_options'],
            'eval'      => [
                'submitOnChange' => true,
                'tl_class'       => 'w50',
            ],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'metamodel_view'       => [
            'label'            => &$GLOBALS['TL_LANG'][$table]['metamodel_view'],
            'inputType'        => 'select',
            'options_callback' => ['Ferienpass\Helper\Dca', 'getOffersMetaModelRenderSettings'],
            'eval'             => [
                'inlcudeBlankOption' => true,
                'tl_class'           => 'w50',
            ],
            'sql'              => "int(10) NOT NULL default '0'",
        ],
        'scope'                => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['scope'],
            'inputType' => 'select',
            'options'   => [
                'single',
                'full',
            ],
            'eval'      => [
                'submitOnChange' => true,
                'tl_class'       => 'w50',
            ],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'filesystem'           => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['filesystem'],
            'inputType' => 'select',
            'options'   => [
                'local',
                'sendToBrowser',
                'dropbox',
            ],
            'reference' => &$GLOBALS['TL_LANG'][$table]['filesystem_options'],
            'eval'      => [
                'submitOnChange' => true,
                'tl_class'       => 'w50 clr',
            ],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'offer_image_path'     => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['offer_image_path'],
            'inputType' => 'fileTree',
            'eval'      => [
                'fieldType' => 'radio',
                'files'     => false,
                'tl_class'  => 'w50 clr',
            ],
            'sql'       => "binary(16) NULL",
        ],
        'host_logo_path'       => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['host_logo_path'],
            'inputType' => 'fileTree',
            'eval'      => [
                'fieldType' => 'radio',
                'files'     => false,
                'tl_class'  => 'w50',
            ],
            'sql'       => "binary(16) NULL",
        ],
        'combine_variants'     => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['combine_variants'],
            'inputType' => 'checkbox',
            'eval'      => [
                'tl_class' => 'w50 m12',
            ],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'export_file_name'     => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['export_file_name'],
            'inputType' => 'text',
            'eval'      => [
                'mandatory' => true,
                'tl_class'  => 'w50',
            ],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'dropbox_access_token' => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['dropbox_access_token'],
            'inputType' => 'request_access_token',
            'eval'      => [
                'tl_class' => 'long clr',
            ],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'dropbox_uid'          => [
            'sql' => "int(10) NOT NULL default '0'",
        ],
        'dropbox_cursor'       => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'path_prefix'          => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['path_prefix'],
            'inputType' => 'text',
            'eval'      => [
                'trailingSlash' => false,
                'tl_class'      => 'w50',
            ],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'sync'                 => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['sync'],
            'inputType' => 'checkbox',
            'eval'      => [
                'submitOnChange' => 'true',
                'tl_class'       => 'w50 m12',
            ],
            'sql'       => "char(1) NOT NULL default ''",
        ],
        'ical_fields'          => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['ical_fields'],
            'inputType' => 'multiColumnWizard',
            'eval'      => [
                'columnFields' => [
                    'ical_field'          => [
                        'label'     => &$GLOBALS['TL_LANG'][$table]['ical_field'],
                        'inputType' => 'select',
                        'options'   => [
                            'dtStart',
                            'dtEnd',
                            'summary',
                            'description',
                            'location',
                        ],
                        'eval'      => ['style' => 'width:250px', 'chosen' => true],
                    ],
                    'metamodel_attribute' => [
                        'label'            => &$GLOBALS['TL_LANG'][$table]['metamodel_attribute'],
                        'inputType'        => 'conditionalselect',
                        'options_callback' => ['Ferienpass\Helper\Dca', 'getMetaModelsAttributes'],
                        'eval'             => [
                            'condition' => 'mm_ferienpass',
                            'chosen'    => true,
                            'style'     => 'width:250px',
                        ],
                    ],
                ],
                'tl_class'     => 'clr',
            ],
            'sql'       => "text NULL",
        ],
    ],
];
