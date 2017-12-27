<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


$table = Richardhj\ContaoFerienpassBundle\Model\Document::getTable();


$GLOBALS['TL_DCA'][$table] = [
    // Config
    'config'       => [
        'dataContainer'    => 'General',
        'enableVersioning' => true,
        'sql'              => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],

    // List
    'list'         => [
        'sorting'           => [
            'mode'        => 1,
            'fields'      => ['name'],
            'flag'        => 1,
            'panelLayout' => 'filter;search,limit',
        ],
        'label'             => [
            'fields' => ['name'],
            'format' => '%s',
        ],
        'global_operations' => [
            'back' => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['backBT'],
                'href'       => 'mod=&table=',
                'class'      => 'header_back',
                'attributes' => 'onclick="Backend.getScrollOffset();"',
            ],
            'new'  => [
                'label'      => &$GLOBALS['TL_LANG'][$table]['new'],
                'href'       => 'act=create',
                'class'      => 'header_new',
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

    // MetaPalettes
    'metapalettes' => [
        'default' => [
            'type'     => [
                'name',
                'type',
            ],
            'config'   => [
                'documentTitle',
                'fileTitle',
            ],
            'template' => [
                'documentTpl',
                'gallery',
                'collectionTpl',
                'orderCollectionBy',
            ],
        ],
    ],

    // Fields
    'fields'       => [
        'id'                => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp'            => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'name'              => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['name'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'mandatory' => true,
                'maxlength' => 255,
                'tl_class'  => 'w50',
            ],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'documentTitle'     => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['documentTitle'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'mandatory'      => true,
                'decodeEntities' => true,
                'maxlength'      => 255,
                'tl_class'       => 'w50',
            ],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'fileTitle'         => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['fileTitle'],
            'exclude'   => true,
            'inputType' => 'text',
            'eval'      => [
                'mandatory'      => true,
                'decodeEntities' => true,
                'maxlength'      => 255,
                'tl_class'       => 'w50',
            ],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'documentTpl'       => [
            'label'            => &$GLOBALS['TL_LANG'][$table]['documentTpl'],
            'exclude'          => true,
            'inputType'        => 'select',
            'options_callback' => \Controller::getTemplateGroup('fp_document_'),
            'eval'             => [
                'includeBlankOption' => true,
                'chosen'             => true,
                'tl_class'           => 'w50',
                'mandatory'          => true,
            ],
            'sql'              => "varchar(64) NOT NULL default ''",
        ],
        'collectionTpl'     => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['collectionTpl'],
            'exclude'   => true,
            'default'   => 'iso_collection_invoice',
            'inputType' => 'select',
            'options'   => \Controller::getTemplateGroup('fp_collection_'),
            'eval'      => [
                'includeBlankOption' => true,
                'chosen'             => true,
                'tl_class'           => 'w50',
                'mandatory'          => true,
            ],
            'sql'       => "varchar(64) NOT NULL default ''",
        ],
        'orderCollectionBy' => [ //FIXME

            'label'     => &$GLOBALS['TL_LANG'][$table]['orderCollectionBy'],
            'exclude'   => true,
            'default'   => 'asc_id',
            'inputType' => 'select',
            'options'   => $GLOBALS['TL_LANG']['MSC']['iso_orderCollectionBy'],
            'eval'      => [
                //'mandatory'=>true,
                'tl_class' => 'w50',
            ],
            'sql'       => "varchar(16) NOT NULL default ''",
        ],
    ],
];
