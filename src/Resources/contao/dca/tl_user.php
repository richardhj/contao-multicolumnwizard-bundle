<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
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
