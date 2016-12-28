<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


/**
 * Back end modules
 */
$GLOBALS['TL_LANG']['MOD']['ferienpass'] = 'Ferienpass';
$GLOBALS['TL_LANG']['MOD']['offers'][0] = 'Angebote';
$GLOBALS['TL_LANG']['MOD']['offers'][1] = 'Die Ferienpass-Angebote verwalten.';
$GLOBALS['TL_LANG']['MOD']['ferienpass_management'][0] = 'Management';
$GLOBALS['TL_LANG']['MOD']['ferienpass_management'][1] = 'Den Ferienpass verwalten';
$GLOBALS['TL_LANG']['MOD']['ferienpass_attendances'][0] = 'Anmeldungen';
$GLOBALS['TL_LANG']['MOD']['ferienpass_attendances'][1] = 'Die Ferienpass-Anmeldungen einsehen';


/**
 * Front end modules
 */
$GLOBALS['TL_LANG']['FMD']['offer_editing'][0] = 'Angebot-Bearbeitung';
$GLOBALS['TL_LANG']['FMD']['offer_editing'][1] = 'Bearbeiten Sie mit diesem Modul das jeweilige Angebot.';
$GLOBALS['TL_LANG']['FMD']['offers_management'][0]	= 'Angebotsverwaltung';
$GLOBALS['TL_LANG']['FMD']['offers_management'][1]	= 'Dieses Modul listet alle Angebote des jeweiligen Anbieters an.';
$GLOBALS['TL_LANG']['FMD']['items_editing_actions'][0] = 'Elementverwaltung Aktionen';
$GLOBALS['TL_LANG']['FMD']['items_editing_actions'][1] = 'Dieses Modul muss eingebunden werden, um die Aktionen der Elementverwaltung ausführen zu können.';
$GLOBALS['TL_LANG']['FMD']['calendar_offers'][0] = 'Kalender mit Ferienpass-Angeboten';
$GLOBALS['TL_LANG']['FMD']['calendar_offers'][1] = 'Verwenden Sie dieses Modul für die Ausgabe der Ferienpass-Angebote in einem Kalender.';
$GLOBALS['TL_LANG']['FMD']['offer_applicationlist'][0] = 'Teilnehmerliste';
$GLOBALS['TL_LANG']['FMD']['offer_applicationlist'][1] = 'Dieses Modul ermöglicht die Anmeldung zu einem Angebot.';
$GLOBALS['TL_LANG']['FMD']['offer_applicationlisthost'][0] = 'Teilnehmerliste für Veranstalter';
$GLOBALS['TL_LANG']['FMD']['offer_applicationlisthost'][1] = 'Dieses Modul listet dem Veranstalter alle Teilnehmer auf.';


/**
 * Ferienpass modules
 */
$GLOBALS['TL_LANG']['FPMD']['management_module'] = 'Ferienpass-Verwaltung';

$GLOBALS['TL_LANG']['FPMD']['data_processing'] = 'Datenverarbeitung';

$GLOBALS['TL_LANG']['FPMD']['tools'] = 'Tools';
$GLOBALS['TL_LANG']['FPMD']['erase_member_data'][0] = 'Personenbezogene Daten löschen';
$GLOBALS['TL_LANG']['FPMD']['erase_member_data'][1] = 'Die personenenbezogenen Daten der Eltern löschen';

$GLOBALS['TL_LANG']['FPMD']['setup'] = 'Einstellungen';
$GLOBALS['TL_LANG']['FPMD']['data_processings'][0] = 'Datenverarbeitungen';
$GLOBALS['TL_LANG']['FPMD']['data_processings'][1] = 'Die Datenverabeitungen (z.B. Export der Angebote) konfigurieren';
$GLOBALS['TL_LANG']['FPMD']['documents'][0] = 'Dokumente';
$GLOBALS['TL_LANG']['FPMD']['documents'][1] = 'Die Dokumente (z.B. für exportierte PDFs) konfigurieren';
$GLOBALS['TL_LANG']['FPMD']['attendance_status'][0] = 'Teilnahme-Status';
$GLOBALS['TL_LANG']['FPMD']['attendance_status'][1] = 'Die möglichen Status einer Teilnahme konfigurieren';
$GLOBALS['TL_LANG']['FPMD']['ferienpass_config'][0] = 'Konfiguration';
$GLOBALS['TL_LANG']['FPMD']['ferienpass_config'][1] = 'Die grundlegende Konfiguration vornehmen';

$objProcessings = \Ferienpass\Model\DataProcessing::findAll();

while (null !== $objProcessings && $objProcessings->next())
{
	$GLOBALS['TL_LANG']['FPMD']['data_processing_' .$objProcessings->id][0] = $objProcessings->name;
	$GLOBALS['TL_LANG']['FPMD']['data_processing_' .$objProcessings->id][1] = sprintf('Die Datenverarbeitung "%s" durchführen. Synchronisierungen müssen nur einmal getriggert werden.', $objProcessings->name);
}
