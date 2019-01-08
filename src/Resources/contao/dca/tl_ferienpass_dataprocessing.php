<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

use MetaModels\Factory;
use Richardhj\ContaoFerienpassBundle\Helper\Dca;


$GLOBALS['TL_DCA']['tl_ferienpass_dataprocessing'] = [
    // Config
    'config'                => [
        'dataContainer' => 'General',
        'sql'           => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    // List
    'list'                  => [
        'sorting'           => [
            'mode'   => 1,
            'fields' => [
                'name',
            ],
            'flag'   => 1,
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
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['edit'],
                'href'  => 'act=edit',
                'icon'  => 'edit.gif',
            ],
            'copy'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['copy'],
                'href'  => 'act=copy',
                'icon'  => 'copy.gif',
            ],
            'delete' => [
                'label'      => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.gif',
                'attributes' => 'onclick="if (!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm']
                                .'\')) return false; Backend.getScrollOffset();"',
            ],
            'show'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['show'],
                'href'  => 'act=show',
                'icon'  => 'show.gif',
            ],
            'run'   => [
                'label' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['run'],
                'href'  => 'key=execute',
                'icon'  => 'bundles/richardhjcontaoferienpass/img/play-button.svg',
            ],
        ],
    ],

    // Meta Palettes
    'metapalettes'          => [
        'default' => [
            'title'      => [
                'name',
            ],
            'format'     => [
                'format',
            ],
            'filesystem' => [
                'filesystem',
            ],
            'scope'      => [
                'metamodel_filtering',
                'metamodel_filterparams',
                'metamodel_sortby',
                'metamodel_sortby_direction',
                'metamodel_offset',
                'metamodel_limit',
                'static_dirs',
            ],
        ],
    ],
    // Meta Sub Palettes
    'metasubpalettes'       => [
        'combine_variants' => [
        ],
    ],
    // Meta SubSelect Palettes
    'metasubselectpalettes' => [
        'format'     => [
            'xml'  => [
                'metamodel_view',
                'xml_single_file',
                'combine_variants',
                'variant_delimiters',
            ],
            'ical' => [
                'ical_fields',
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
        'id'                         => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp'                     => [
            'sql' => 'int(10) unsigned NOT NULL default \'0\'',
        ],
        'name'                       => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['name'],
            'inputType' => 'text',
            'eval'      => [
                'mandatory' => true,
                'maxlength' => 255,
                'tl_class'  => 'w50',
            ],
            'sql'       => 'varchar(255) NOT NULL default \'\'',
        ],
        'format'                     => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['format'],
            'inputType' => 'select',
            'default'   => 'xml',
            'options'   => [
                'xml',
                'ical',
            ],
            'reference' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['format_options'],
            'eval'      => [
                'submitOnChange'     => true,
                'includeBlankOption' => true,
                'mandatory'          => true,
                'tl_class'           => 'w50',
            ],
            'sql'       => 'varchar(64) NOT NULL default \'\'',
        ],
        'metamodel_view'             => [
            'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['metamodel_view'],
            'inputType'        => 'select',
            'options_callback' => [Dca::class, 'getOffersMetaModelRenderSettings'],
            'eval'             => [
                'includeBlankOption' => true,
                'tl_class'           => 'w50',
            ],
            'sql'              => 'int(10) NOT NULL default \'0\'',
        ],
        'metamodel_filtering'        => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['metamodel_filtering'],
            'inputType' => 'select',
            'eval'      => [
                'includeBlankOption' => true,
                'submitOnChange'     => true,
                'tl_class'           => 'w50',
            ],
            'sql'       => 'int(10) NOT NULL default \'0\'',
        ],
        'metamodel_filterparams'     => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['metamodel_filterparams'],
            'exclude'   => true,
            'inputType' => 'mm_subdca',
            'eval'      => [
                'tl_class' => 'clr m12',
            ],
            'sql'       => 'longblob NULL',
        ],
        'metamodel_sortby'           => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['metamodel_sortby'],
            'exclude'   => true,
            'inputType' => 'select',
            'eval'      => [
                'tl_class'           => 'w50',
                'includeBlankOption' => true,
            ],
            'sql'       => 'varchar(64) NOT NULL default \'0\'',
        ],
        'metamodel_limit'            => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['metamodel_limit'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'tl_class' => 'w50',
            ],
            'sql'       => 'int(10) NOT NULL default \'0\'',
        ],
        'metamodel_offset'           => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['metamodel_offset'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'tl_class' => 'w50',
            ],
            'sql'       => 'int(10) NOT NULL default \'0\'',
        ],
        'metamodel_sortby_direction' => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['metamodel_sortby_direction'],
            'exclude'   => true,
            'inputType' => 'select',
            'options'   => [
                'ASC',
                'DESC',
            ],
            'default'   => 'ASC',
            'eval'      => [
                'tl_class' => 'w50',
            ],
            'sql'       => 'varchar(4) NOT NULL default \'\'',
        ],
        'filesystem'                 => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['filesystem'],
            'inputType' => 'select',
            'options'   => [
                'local',
                'sendToBrowser',
                'dropbox',
            ],
            'reference' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['filesystem_options'],
            'eval'      => [
                'submitOnChange' => true,
                'tl_class'       => 'w50 clr',
            ],
            'sql'       => 'varchar(64) NOT NULL default \'\'',
        ],
        'static_dirs'                => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['static_dirs'],
            'inputType' => 'fileTree',
            'eval'      => [
                'multiple'  => 'true',
                'fieldType' => 'checkbox',
                'files'     => false,
                'tl_class'  => 'clr',
            ],
            'sql'       => 'blob NULL',
        ],
        'combine_variants'           => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['combine_variants'],
            'inputType' => 'checkbox',
            'eval'      => [
                'tl_class'       => 'w50 m12',
                'submitOnChange' => true,
            ],
            'sql'       => 'char(1) NOT NULL default \'\'',
        ],
        'variant_delimiters'         => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['variant_delimiters'],
            'inputType' => 'multiColumnWizard',
            'eval'      => [
                'columnFields' => [
                    'metamodel_attribute' => [
                        'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['metamodel_attribute'],
                        'inputType'        => 'conditionalselect',
                        'options_callback' => function () {
                            /** @var Factory $factory */
                            $factory   = \Contao\System::getContainer()->get('metamodels.factory');
                            $metaModel = $factory->getMetaModel('mm_ferienpass');
                            if (null === $metaModel) {
                                return [];
                            }

                            $return = [];
                            foreach ($metaModel->getAttributes() as $attrName => $attribute) {
                                $return[$attrName] = $attribute->getName();
                            }

                            return $return;
                        },
                        'eval'             => [
                            'condition'          => 'mm_ferienpass',
                            'chosen'             => true,
                            'includeBlankOption' => true,
                            'style'              => 'width:200px',
                        ],
                    ],
                    'delimiter'           => [
                        'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['delimiter'],
                        'inputType' => 'text',
                        'eval'      => [
                            'style' => 'width:50px',
                        ],
                    ],
                    'newline'             => [
                        'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['newline'],
                        'inputType' => 'checkbox',
                    ],
                    'newline_position'    => [
                        'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['newline_position'],
                        'reference' => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['newline_positions'],
                        'inputType' => 'select',
                        'default'   => 'after',
                        'options'   => [
                            'before',
                            'after',
                        ],
                        'eval'      => [
                            'style' => 'width:150px',
                        ],
                    ],
                ],
                'tl_class'     => 'clr',
            ],
            'sql'       => 'text NULL',
        ],
        'xml_single_file'            => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['xml_single_file'],
            'inputType' => 'checkbox',
            'eval'      => [
                'tl_class' => 'w50 m12',
            ],
            'sql'       => 'char(1) NOT NULL default \'\'',
        ],
        'export_file_name'           => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['export_file_name'],
            'inputType' => 'text',
            'eval'      => [
                'mandatory' => true,
                'tl_class'  => 'w50',
            ],
            'sql'       => 'varchar(255) NOT NULL default \'\'',
        ],
        'dropbox_access_token'       => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['dropbox_access_token'],
            'inputType' => 'request_access_token',
            'eval'      => [
                'tl_class' => 'long clr',
            ],
            'sql'       => 'varchar(255) NULL',
        ],
        'dropbox_uid'                => [
            'sql' => 'int(10) NULL',
        ],
        'dropbox_cursor'             => [
            'sql' => 'varchar(255) NULL',
        ],
        'path_prefix'                => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['path_prefix'],
            'inputType' => 'text',
            'eval'      => [
                'trailingSlash' => false,
                'tl_class'      => 'w50',
            ],
            'sql'       => 'varchar(255) NOT NULL default \'\'',
        ],
        'sync'                       => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['sync'],
            'inputType' => 'checkbox',
            'eval'      => [
                'submitOnChange' => 'true',
                'tl_class'       => 'w50 m12',
            ],
            'sql'       => 'char(1) NOT NULL default \'\'',
        ],
        'ical_fields'                => [
            'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['ical_fields'],
            'inputType' => 'multiColumnWizard',
            'eval'      => [
                'columnFields' => [
                    'ical_field'          => [
                        'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['ical_field'],
                        'inputType' => 'select',
                        'options'   => [
                            'summary',
                            'description',
                            'location',
                        ],
                        'eval'      => [
                            'style'  => 'width:250px',
                            'chosen' => true,
                        ],
                    ],
                    'metamodel_attribute' => [
                        'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['metamodel_attribute'],
                        'inputType'        => 'conditionalselect',
                        'eval'             => [
                            'condition' => 'mm_ferienpass',
                            'chosen'    => true,
                            'style'     => 'width:250px',
                        ],
                    ],
                ],
                'tl_class'     => 'clr',
            ],
            'sql'       => 'text NULL',
        ],
    ],
];
