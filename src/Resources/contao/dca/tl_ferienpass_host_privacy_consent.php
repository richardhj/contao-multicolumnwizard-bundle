<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE
 */

$GLOBALS['TL_DCA']['tl_ferienpass_host_privacy_consent'] = [
    // Config
    'config' => [
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ]
        ]
    ],

    // Fields
    'fields' => [
        'id'        => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp'    => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'member'    => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'type'    => [
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'statement_hash' => [
            'sql' => "varchar(40) NOT NULL default ''",
        ],
        'form_data' => [
            'sql' => 'text NULL',
        ],
    ],
];
