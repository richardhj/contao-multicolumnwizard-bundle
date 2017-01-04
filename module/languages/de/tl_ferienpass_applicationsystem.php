<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


$table = Ferienpass\Model\ApplicationSystem::getTable();


/**
 * Legends
 */
//$GLOBALS['TL_LANG'][$table]['offer_legend'] = 'Angebot';
//$GLOBALS['TL_LANG'][$table]['participant_legend'] = 'Teilnehmer';
//$GLOBALS['TL_LANG'][$table]['status_legend'] = 'Status';


/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_ferienpass_config']['maxApplicationsPerDay'][0] = 'Maximale Angebot-Anmeldungen per Tag';
$GLOBALS['TL_LANG']['tl_ferienpass_config']['maxApplicationsPerDay'][1] = 'Geben Sie an, wie oft sich ein Teilnehmer (nicht Mitglied) für Angebote an einem einzelnen Tag anmelden darf. Bei 0 deaktiviert.';


/**
 * Actions
 */
//$GLOBALS['TL_LANG'][$table]['new'][0] = 'Neue Anmeldung';
//$GLOBALS['TL_LANG'][$table]['new'][1] = 'Eine neue Anmeldung vornehmen';
//$GLOBALS['TL_LANG'][$table]['show'][0] = 'Details zeigen';
//$GLOBALS['TL_LANG'][$table]['show'][1] = 'Details von der Ameldung ID %s anzeigen';
