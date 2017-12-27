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
$table = \MemberModel::getTable();


/**
 * Legends
 */
$GLOBALS['TL_LANG'][$table]['ferienpass_legend'] = 'Ferienpass';


/**
 * Fields
 */
$GLOBALS['TL_LANG'][$table]['ferienpass_host'][0] = 'Veranstalter';
$GLOBALS['TL_LANG'][$table]['ferienpass_host'][1] = 'Wählen Sie den Veranstalter aus, zu dem dieses Mitglied gehört.';
$GLOBALS['TL_LANG'][$table]['persist'][0] = 'nicht automatisch löschen';
$GLOBALS['TL_LANG'][$table]['persist'][1] = 'Diesen Account von der automatischen Löschung ausschließen';
