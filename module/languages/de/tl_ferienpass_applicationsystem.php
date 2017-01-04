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
$GLOBALS['TL_LANG'][$table]['config_legend'] = 'Titel und Konfiguration';
$GLOBALS['TL_LANG'][$table]['published_legend'] = 'Fristen';


/**
 * Fields
 */
$GLOBALS['TL_LANG'][$table]['title'][0] = 'Titel';
$GLOBALS['TL_LANG'][$table]['title'][1] = 'Geben Sie einen internen Namen für das Anmeldesytem ein.';
$GLOBALS['TL_LANG'][$table]['type'][0] = 'Typ';
$GLOBALS['TL_LANG'][$table]['type'][1] = 'Das Anmeldesystem, um das es sich handelt.';
$GLOBALS['TL_LANG'][$table]['maxApplicationsPerDay'][0] = 'Maximale Angebot-Anmeldungen per Tag';
$GLOBALS['TL_LANG'][$table]['maxApplicationsPerDay'][1] = 'Geben Sie an, wie oft sich ein Teilnehmer (nicht Mitglied) für Angebote an einem einzelnen Tag anmelden darf. Bei 0 deaktiviert.';
$GLOBALS['TL_LANG'][$table]['published'][0] = 'Das Anmeldesytem verwenden';
$GLOBALS['TL_LANG'][$table]['published'][1] = 'Wählen Sie aus, ob das Anmeldesytem für den Ferienpass zum Einsatz kommen soll.';
$GLOBALS['TL_LANG'][$table]['start'][0] = 'Fristbeginn';
$GLOBALS['TL_LANG'][$table]['start'][1] = 'Geben Sie an, ab wann das Anmeldesystem aktiv ist.';
$GLOBALS['TL_LANG'][$table]['stop'][0] = 'Fristende';
$GLOBALS['TL_LANG'][$table]['stop'][1] = 'Geben Sie an, bis wann das Anmeldesytem aktiv ist.';


/**
 * Actions
 */
//$GLOBALS['TL_LANG'][$table]['new'][0] = 'Neue Anmeldung';
//$GLOBALS['TL_LANG'][$table]['new'][1] = 'Eine neue Anmeldung vornehmen';
//$GLOBALS['TL_LANG'][$table]['show'][0] = 'Details zeigen';
//$GLOBALS['TL_LANG'][$table]['show'][1] = 'Details von der Ameldung ID %s anzeigen';
