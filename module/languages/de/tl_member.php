<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
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
