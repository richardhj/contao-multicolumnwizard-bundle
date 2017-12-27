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


/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_ferienpass_document']['name'][0] = 'Dokumentname';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['name'][1] = 'Geben Sie einen Namen für dieses Dokument ein. Dies wird nur im Backend benutzt.';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['type'][0] = 'Dokumententyp';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['type'][1] = 'Wählen Sie eine mögliche Renderingklasse für das Dokument.';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['logo'][0] = 'Logo';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['logo'][1] = 'Wählen Sie ein Logo.';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['documentTitle'][0] = 'Dokumententitel';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['documentTitle'][1] = 'Sie können Simple-Tokens ("collection_*" feldname * entspricht dem Datenbankfeld der Sammlung) verwenden, um den Dateinamen zu generieren (z.B. "Rechnungstitel ##collection_document_number##").';//FIXME
$GLOBALS['TL_LANG']['tl_ferienpass_document']['fileTitle'][0] = 'Dateiname';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['fileTitle'][1] = 'Sie können Simple-Tokens ("collection_*" feldname * entspricht dem Datenbankfeld der Sammlung) verwenden, um den Dateinamen zu generieren (z.B. "Rechnungstitel ##collection_document_number##").';//FIXME
$GLOBALS['TL_LANG']['tl_ferienpass_document']['documentTpl'][0] = 'Dokumenten-Template';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['documentTpl'][1] = 'Wählen Sie ein Template aus, mit dem Sie das Dokument rendern möchten.';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['gallery'][0] = 'Galerie';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['gallery'][1] = 'Wählen Sie eine Galerie, um die Bilder zu rendern.';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['collectionTpl'][0] = 'Sammlung-Template';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['collectionTpl'][1] = 'Bitte wählen Sie ein Template aus, mit welchem Sie die Sammlung rendern möchten.';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['orderCollectionBy'][0] = 'Sortierung';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['orderCollectionBy'][1] = 'Definieren Sie, in welcher Reihenfolge die Einträge einer Sammlung aufgelistet werden sollen.';


/**
 * Buttons
 */
$GLOBALS['TL_LANG']['tl_ferienpass_document']['new'][0] = 'Neues Dokument';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['new'][1] = 'Neues Dokument erstellen';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['edit'][0] = 'Dokument bearbeiten';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['edit'][1] = 'Dokument ID %s bearbeiten';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['copy'][0] = 'Dokument kopieren';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['copy'][1] = 'Dokument ID %s kopieren';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['delete'][0] = 'Dokument löschen';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['delete'][1] = 'Dokument ID %s löschen';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['show'][0] = 'Dokumentendetails';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['show'][1] = 'Details vom Dokument ID %s anzeigen';


/**
 * Legends
 */
$GLOBALS['TL_LANG']['tl_ferienpass_document']['type_legend'] = 'Name & Typ';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['config_legend'] = 'Allgemeine Einstellungen';
$GLOBALS['TL_LANG']['tl_ferienpass_document']['template_legend'] = 'Template';
