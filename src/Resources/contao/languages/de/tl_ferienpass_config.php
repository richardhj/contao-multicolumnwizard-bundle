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

$table = Richardhj\ContaoFerienpassBundle\Model\Config::getTable();


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
$GLOBALS['TL_LANG'][$table]['ageCheckMethod'][0] = 'Altersüberprüfung';
$GLOBALS['TL_LANG'][$table]['ageCheckMethod'][1] = 'Wählen Sie, wie das Alter des Kindes überprüft werden soll.';


/**
 * References
 */
$GLOBALS['TL_LANG'][$table]['ageCheckMethodOptions']['exact'] = 'Exakt';
$GLOBALS['TL_LANG'][$table]['ageCheckMethodOptions']['vagueOnYear'] = 'Kind darf das Jahr so alt werden/gewesen sein';
