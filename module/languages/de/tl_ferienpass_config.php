<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

$table = Ferienpass\Model\Config::getTable();


/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_ferienpass_config']['restrictions_legend'] = 'Restriktionen';


/**
 * Fields
 */
// Restrictions
$GLOBALS['TL_LANG'][$table]['registrationAllowedZipCodes'][0] = 'Erlaubte Postleitzahlen bei Registrierung';
$GLOBALS['TL_LANG'][$table]['registrationAllowedZipCodes'][1] = 'Geben Sie die Postleitzahlen an, für welche nur eine Registrierung möglich ist.';
$GLOBALS['TL_LANG'][$table]['registrationRequiredFields'][0] = 'Pflichtfelder für Benutzerdaten';
$GLOBALS['TL_LANG'][$table]['registrationRequiredFields'][1] = 'Wählen Sie die Felder aus, die von Mitgliedern angegeben werden müssen.';
