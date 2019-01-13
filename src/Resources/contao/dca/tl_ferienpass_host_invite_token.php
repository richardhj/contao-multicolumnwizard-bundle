<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

$GLOBALS['TL_DCA']['tl_ferienpass_host_invite_token'] = [
    'config' => [
        'sql' => [
            'keys' => [
                'id'              => 'primary',
                'inviting_member' => 'index',
                'token'           => 'unique',
            ],
        ],
    ],
    'fields' => [
        'id'              => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp'          => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'inviting_member' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'invited_email'   => [
            'sql' => "varchar(40) NOT NULL default ''"
        ],
        'token'           => [
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'host'            => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'expires'         => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ]
    ],
];