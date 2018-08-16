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