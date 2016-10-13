<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

use Ferienpass\Model\AttendanceStatus;


$table = Ferienpass\Model\Attendance::getTable();


/**
 * Table tl_ferienpass_attendance
 */
$GLOBALS['TL_DCA'][$table] = array
(

	// Config
	'config' => array
	(
		'sql'               => array
		(
			'keys' => array
			(
				'id' => 'primary',
			)
		),
	),

	// Fields
	'fields' => array
	(
        'id'          => array
		(
			'sql'               =>  "int(10) unsigned NOT NULL auto_increment",
		),
        'tstamp'      => array
		(
			'sql'               =>  "int(10) unsigned NOT NULL default '0'",
		),
        'offer'       => array
		(
			'sql'               =>  "int(10) unsigned NOT NULL default '0'",
		),
        'participant' => array
		(
			'sql'               =>  "int(10) unsigned NOT NULL default '0'",
		),
        'status'      => array
		(
			'sql'               =>  "int(10) unsigned NOT NULL default '0'",
			'relation'          => array('type' => 'hasOne', 'table' => AttendanceStatus::getTable())
		)
	)
);
