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


/**
 * Table tl_metamodel_rendersetting
 */
$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['metapalettes']['timestamp extends default']['timesettings'][] =
    'outputAge';

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['outputAge'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['outputAge'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'w50 m12',
    ],
    'sql'       => "char(1) NOT NULL default ''",
];


$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['metapalettes']['offer_date extends default'] = [
    'timesettings' => [
        'timeformatStart',
        'timeformatEnd',
        'timeformatStartEqualDay',
        'timeformatEndEqualDay',

    ],
];

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['timeformatStart'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['timeformatStart'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => [
        'tl_class' => 'w50',
    ],
    'sql'       => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['timeformatEnd'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['timeformatEnd'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => [
        'tl_class' => 'w50',
    ],
    'sql'       => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['timeformatStartEqualDay'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['timeformatStartEqualDay'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => [
        'tl_class' => 'w50',
    ],
    'sql'       => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_metamodel_rendersetting']['fields']['timeformatEndEqualDay'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_rendersetting']['timeformatEndEqualDay'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => [
        'tl_class' => 'w50',
    ],
    'sql'       => "varchar(64) NOT NULL default ''",
];
