<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'][]    = 'ferienpass_metamodel_list';
$GLOBALS['TL_DCA']['tl_content']['palettes']['metamodel_content'] = str_replace(
    ';{protected_legend',
    ';{ferienpass_legend:hide},ferienpass_metamodel_list;{protected_legend',
    $GLOBALS['TL_DCA']['tl_content']['palettes']['metamodel_content']
);

$GLOBALS['TL_DCA']['tl_content']['subpalettes']['ferienpass_metamodel_list_host_edit'] =
    'pass_release,jumpTo_application_list';
$GLOBALS['TL_DCA']['tl_content']['subpalettes']['ferienpass_metamodel_list_show']      = '';

$GLOBALS['TL_DCA']['tl_content']['fields']['ferienpass_metamodel_list'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['ferienpass_metamodel_list'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => [
        'host_edit',
        'show'
    ],
    'eval'      => [
        'submitOnChange'     => true,
        'includeBlankOption' => true,
        'tl_class'           => 'w50'
    ],
    'sql'       => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['jumpTo_application_list'] = [
    'label'      => &$GLOBALS['TL_LANG']['tl_content']['jumpTo_application_list'],
    'exclude'    => true,
    'inputType'  => 'pageTree',
    'foreignKey' => 'tl_page.title',
    'eval'       => [
        'fieldType' => 'radio'
    ],
    'relation'   => [
        'type' => 'hasOne',
        'load' => 'eager'
    ],
    'sql'        => "int(10) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['pass_release'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_content']['pass_release'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => [
        'current',
        'previous'
    ],
    'eval'      => [
        'fieldType'          => 'radio',
        'tl_class'           => 'w50',
        'mandatory'          => true,
        'includeBlankOption' => true,
    ],
    'sql'       => "varchar(64) NOT NULL default ''",
];
