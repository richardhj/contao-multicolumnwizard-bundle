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

$GLOBALS['TL_DCA']['tl_metamodel_offer_date'] = [

    // Config
    'config' => [
        'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ]
    ],

    // Fields
    'fields' => [
        'id'      => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment'
        ],
        'tstamp'  => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'att_id'  => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'item_id' => [
            'sql' => "int(10) unsigned NOT NULL default '0'"
        ],
        'start'   => [
            'sql' => 'int(10) NULL'
        ],
        'end'     => [
            'sql' => 'int(10) NULL'
        ],
    ]
];
