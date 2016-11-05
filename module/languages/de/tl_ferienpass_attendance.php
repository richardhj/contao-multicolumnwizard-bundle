<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


$table = Ferienpass\Model\Attendance::getTable();


/**
 * Legends
 */
$GLOBALS['TL_LANG'][$table]['offer_legend'] = 'Angebot';
$GLOBALS['TL_LANG'][$table]['participant_legend'] = 'Teilnehmer';
$GLOBALS['TL_LANG'][$table]['status_legend'] = 'Status';


/**
 * Fields
 */
$GLOBALS['TL_LANG'][$table]['tstamp'][0] = 'Zeitpunkt';
$GLOBALS['TL_LANG'][$table]['tstamp'][1] = 'Zeitpunkt der Anmeldung';
$GLOBALS['TL_LANG'][$table]['offer'][0] = 'Angebot';
$GLOBALS['TL_LANG'][$table]['offer'][1] = 'Bitte wählen Sie das Angebot aus, zu welcher angemeldet werden soll.';
$GLOBALS['TL_LANG'][$table]['participant'][0] = 'Teilnehmer';
$GLOBALS['TL_LANG'][$table]['participant'][1] = 'Bitte wählen Sie aus, welcher Teilnehmer angemeldet werden soll.';
$GLOBALS['TL_LANG'][$table]['status'][0] = 'Status';
$GLOBALS['TL_LANG'][$table]['status'][1] = 'Der Status der Anmeldung';


/**
 * Actions
 */
$GLOBALS['TL_LANG'][$table]['new'][0] = 'Neue Anmeldung';
$GLOBALS['TL_LANG'][$table]['new'][1] = 'Eine neue Anmeldung vornehmen';
$GLOBALS['TL_LANG'][$table]['show'][0] = 'Details zeigen';
$GLOBALS['TL_LANG'][$table]['show'][1] = 'Details von der Ameldung ID %s anzeigen';
