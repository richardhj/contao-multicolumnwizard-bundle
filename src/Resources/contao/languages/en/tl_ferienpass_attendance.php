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

$table = Richardhj\ContaoFerienpassBundle\Model\Attendance::getTable();


/**
 * Legends
 */
$GLOBALS['TL_LANG'][$table]['offer_legend'] = 'Offer';
$GLOBALS['TL_LANG'][$table]['participant_legend'] = 'Participant';
$GLOBALS['TL_LANG'][$table]['status_legend'] = 'Status';


/**
 * Fields
 */
$GLOBALS['TL_LANG'][$table]['tstamp'][0] = 'Timestamp';
$GLOBALS['TL_LANG'][$table]['tstamp'][1] = 'Date and time of application.';
$GLOBALS['TL_LANG'][$table]['offer'][0] = 'Offer';
$GLOBALS['TL_LANG'][$table]['offer'][1] = 'Please choose the offer the application refers to.';
$GLOBALS['TL_LANG'][$table]['participant'][0] = 'Particpant';
$GLOBALS['TL_LANG'][$table]['participant'][1] = 'Please choose the particpant which is going to attend.';
$GLOBALS['TL_LANG'][$table]['status'][0] = 'Status';
$GLOBALS['TL_LANG'][$table]['status'][1] = 'The status of the application.';


/**
 * Actions
 */
$GLOBALS['TL_LANG'][$table]['new'][0] = 'New attendance';
$GLOBALS['TL_LANG'][$table]['new'][1] = 'Add a new attendance.';
$GLOBALS['TL_LANG'][$table]['edit'][0] = 'Edit';
$GLOBALS['TL_LANG'][$table]['edit'][1] = 'Edit attendance ID %s';
$GLOBALS['TL_LANG'][$table]['delete'][0] = 'Delete';
$GLOBALS['TL_LANG'][$table]['delete'][1] = 'Delete attendance ID %s';
$GLOBALS['TL_LANG'][$table]['show'][0] = 'Details';
$GLOBALS['TL_LANG'][$table]['show'][1] = 'Show details of attendance ID %s';
