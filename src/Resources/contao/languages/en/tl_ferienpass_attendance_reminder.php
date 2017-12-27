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

$table = Richardhj\ContaoFerienpassBundle\Model\AttendanceReminder::getTable();


/**
 * Legends
 */
$GLOBALS['TL_LANG'][$table]['config_legend'] = 'Configuration';
$GLOBALS['TL_LANG'][$table]['published_legend'] = 'Published';


/**
 * Fields
 */
$GLOBALS['TL_LANG'][$table]['remind_before'][0] = 'Remind before…';
$GLOBALS['TL_LANG'][$table]['remind_before'][1] = 'Select the time to trigger the reminder.';
$GLOBALS['TL_LANG'][$table]['nc_notification'][0] = 'Notification';
$GLOBALS['TL_LANG'][$table]['nc_notification'][1] = 'Choose the notification to send.';
$GLOBALS['TL_LANG'][$table]['attendance_status'][0] = 'Attendance status';
$GLOBALS['TL_LANG'][$table]['attendance_status'][1] = 'Filter for an attendance status.';
$GLOBALS['TL_LANG'][$table]['published'][0] = 'Published';
$GLOBALS['TL_LANG'][$table]['published'][1] = 'Use this reminder.';


/**
 * Actions
 */
$GLOBALS['TL_LANG'][$table]['new'][0] = 'New reminder';
$GLOBALS['TL_LANG'][$table]['new'][1] = 'Create a new reminder configuration';
$GLOBALS['TL_LANG'][$table]['show'][0] = 'Show details';
$GLOBALS['TL_LANG'][$table]['show'][1] = 'Show details of reminder ID %s';
