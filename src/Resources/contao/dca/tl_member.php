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

use ContaoCommunityAlliance\MetaPalettes\MetaPalettes;


$GLOBALS['TL_DCA']['tl_member']['list']['label']['fields'][] = 'ferienpass_host:mm_host.name';

MetaPalettes::appendBefore(
    'tl_member',
    'address',
    [
        'ferienpass' => [
            'ferienpass_host',
        ],
    ]
);

$GLOBALS['TL_DCA']['tl_member']['fields']['ferienpass_host'] = [
    'label'         => &$GLOBALS['TL_LANG']['tl_member']['ferienpass_host'],
    'exclude'       => true,
    'inputType'     => 'select',
    'foreignKey'    => 'mm_host.name',
    'eval'          => [
        'includeBlankOption' => true,
        'chosen'             => true,
        'tl_class'           => 'w50',
    ],
    'relation'      => [
        'type' => 'belongsTo',
    ],
    'save_callback' => [
        function ($value, \DataContainer $dc) {
            if ('' === $value && in_array('1', deserialize($dc->activeRecord->groups), true)) {
                throw new \RuntimeException($GLOBALS['TL_LANG']['ERR']['missingHostForMember']);
            }

            return $value;
        },
    ],
    'sql'           => "int(10) NOT NULL default '0'",
];
