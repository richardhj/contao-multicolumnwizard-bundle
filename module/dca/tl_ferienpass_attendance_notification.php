<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


$GLOBALS['TL_DCA']['tl_ferienpass_attendance_notification'] = [
    // Config
    'config' => [
        'sql' => [
            'keys' => [
                'id'                      => 'primary',
                'attendance,notification' => 'unique',
            ]
        ]
    ],

    // Fields
    'fields' => [
        'id'           => [
            'sql' => "int(10) unsigned NOT NULL auto_increment",
        ],
        'tstamp'       => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'attendance'   => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'notification' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
    ],
];
