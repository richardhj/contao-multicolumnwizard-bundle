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
$GLOBALS['TL_DCA']['tl_content']['palettes']['host_editing_list'] = str_replace(
    ',metamodel_layout',
    'jumpTo_application_list,metamodel_layout',
    $GLOBALS['TL_DCA']['tl_content']['palettes']['metamodel_content']
);

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
