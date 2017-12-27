<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


/** @noinspection PhpUndefinedMethodInspection */
$table = \Contao\ContentModel::getTable();


/**
 * Fields
 */
$GLOBALS['TL_LANG'][$table]['ferienpass_legend'] = 'Ferienpass';
$GLOBALS['TL_LANG'][$table]['ferienpass_metamodel_list'][0] = 'Ferienpass-Funktionalität';
$GLOBALS['TL_LANG'][$table]['ferienpass_metamodel_list'][1] = 'Wählen Sie aus, ob und welche Funktionalität die MetaModel-Liste für den Ferienpass hat.';
$GLOBALS['TL_LANG'][$table]['pass_release'][0] = 'Ferienpass-Ausgabe';
$GLOBALS['TL_LANG'][$table]['pass_release'][1] = 'Wählen Sie aus, welche Ferienpass-Ausgabe hier gehandhabt werden soll.';
$GLOBALS['TL_LANG'][$table]['jumpTo_application_list'][0] = 'Teilnehmerliste-Seite';
$GLOBALS['TL_LANG'][$table]['jumpTo_application_list'][1] = 'Wählen Sie die Seite mit der Teilnhemerliste aus.';