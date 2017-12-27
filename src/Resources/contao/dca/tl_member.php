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

use ContaoCommunityAlliance\MetaPalettes\MetaPalettes;


/** @noinspection PhpUndefinedMethodInspection */
$table = \MemberModel::getTable();


/**
 * Config
 */
$GLOBALS['TL_DCA'][$table]['config']['onload_callback'][] = ['Richardhj\ContaoFerienpassBundle\Helper\UserAccount', 'setRequiredFields'];


/**
 * List
 */
$GLOBALS['TL_DCA'][$table]['list']['label']['fields'][] = 'ferienpass_host';


/**
 * Palettes
 */
MetaPalettes::appendBefore(
    $table,
    'address',
    [
        'ferienpass' => [
            'ferienpass_host',
        ],
    ]
);


/**
 * Fields
 */
$GLOBALS['TL_DCA'][$table]['fields']['persist'] = [
    'label'     => &$GLOBALS['TL_LANG'][$table]['persist'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class'   => 'w50 m12',
        'feEditable' => true,
    ],
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA'][$table]['fields']['ferienpass_host'] = [
    'label'         => &$GLOBALS['TL_LANG'][$table]['ferienpass_host'],
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
            if ('' === $value) {
                if (in_array('1', deserialize($dc->activeRecord->groups), true)) {
                    throw new \Exception($GLOBALS['TL_LANG']['ERR']['missingHostForMember']);
                }
            }

            return $value;
        },
    ],
    'sql'           => "int(10) NOT NULL default '0'",
];
