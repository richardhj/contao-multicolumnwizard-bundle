<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


/** @noinspection PhpUndefinedMethodInspection */
$table = UserModel::getTable();


/**
 * Palettes
 */
foreach ($GLOBALS['TL_DCA'][$table]['palettes'] as $name => $palette) {
    if ('__selector__' === $name) {
        continue;
    }

    $GLOBALS['TL_DCA'][$table]['palettes'][$name] = str_replace(',uploader', ',uploader,offer_date_picker', $palette);
}


/**
 * Fields
 */
$GLOBALS['TL_DCA'][$table]['fields']['offer_date_picker'] = [
    'label'     => &$GLOBALS['TL_LANG'][$table]['offer_date_picker'],
    'exclude'   => true,
    'default'   => 1,
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'w50',
    ],
    'sql'       => "char(1) NOT NULL default ''",
];
