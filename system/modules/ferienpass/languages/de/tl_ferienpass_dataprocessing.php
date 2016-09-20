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
 * Fields
 */
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['name'][0] = 'Name';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['name'][1] = 'Geben Sie einen Namen für die Datenverarbeitung an. Dieser wird nur im Backend benutzt.';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['metamodel_view'][0] = 'Render-Einstellung';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['metamodel_view'][1] = 'Wählen Sie die MetaModels-Render-Einstellung, die für das Generieren der XML-Dateien benutzt werden soll.';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['scope'][0] = 'Export-Bereich';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['scope'][1] = 'Wählen Sie den Umfang der Datenverarbeitung bzw. des Exports.';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['filesystem'][0] = 'Dateisystem';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['filesystem'][1] = 'Wählen Sie das Dateisystem aus.';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['offer_image_path'][0] = 'Pfad zu Bildern';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['offer_image_path'][1] = 'Wählen Sie den Ordner mit allen Bildern der Angebote aus.';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['host_logo_path'][0] = 'Pfad zu Logos';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['host_logo_path'][1] = 'Wählen Sie den Ordner mit allen Logos der Veranstalter aus.';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['export_file_name'][0] = 'Export-Dateiname';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['export_file_name'][1] = 'Wählen Sie den Namen der exportierten Datei aus.';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['dropbox_access_token'][0] = 'Dropbox-Access-Token';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['dropbox_access_token'][1] = 'Bitte lassen Sie sich einen Dropbox-Zugangscode generieren.';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['path_prefix'][0] = 'Pfad-Prefix';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['path_prefix'][1] = 'Hier können Sie die Dateien in einen Unterordner exportieren lassen.';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['sync'][0] = 'Synchronisation';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['sync'][1] = 'Wählen Sie aus, ob die Dateien synchronisert werden sollen. Wenn Sie Dateien in der Dropbox löschen, werden sie auch im Websystem gelöscht!';


/**
 * Options
 */
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['filesystem_options']['sendToBrowser'] = 'Dateien komprimiert herunterladen';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['filesystem_options']['dropbox'] = 'Dropbox';


/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['new'][0] = 'Neue Datenverarbeitung';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['new'][1] = 'Neue Datenverarbeitung erstellen';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['edit'][0] = 'Datenverarbeitung bearbeiten';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['edit'][1] = 'Datenverarbeitung ID %s bearbeiten';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['copy'][0] = 'Datenverarbeitung kopieren';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['copy'][1] = 'Datenverarbeitung ID %s kopieren';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['delete'][0] = 'Datenverarbeitung löschen';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['delete'][1] = 'Datenverarbeitung ID %s löschen';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['show'][0] = 'Datenverarbeitungsdetails';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['show'][1] = 'Details von Datenverarbeitung ID %s anzeigen';


/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['title_legend'] = 'Name';
$GLOBALS['TL_LANG']['tl_ferienpass_dataprocessing']['processing_legend'] = 'Konfiguration der Datenverarbeitung';