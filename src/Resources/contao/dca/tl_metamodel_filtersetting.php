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


// Age
$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['metapalettes']['age extends default'] = [
    '+config' => [
        'urlparam',
        'label',
        'template'
    ],
];

// Pass edition
$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['metapalettes']['pass_edition extends default'] = [
    '+config'   => [
        'ferienpass_task',
        'allow_empty',
    ],
    '+fefilter' => [
        'urlparam',
        'label',
        'template',
        'blankoption',
    ],
];

$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['fields']['ferienpass_task'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['ferienpass_task'],
    'reference' => &$GLOBALS['TL_LANG']['tl_metamodel_filtersetting']['ferienpass_task_options'],
    'exclude'   => true,
    'inputType' => 'select',
    'options'   => [
        'show_offers',
        'host_editing'
    ],
    'eval'      => [
        'tl_class'           => 'w50',
        'mandatory'          => true,
        'includeBlankOption' => true,
    ],
    'sql'       => "varchar(64) NOT NULL default ''",
];

// Attendance available
$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['metapalettes']['attendance_available extends default'] = [
    '+config'   => [
        'urlparam'
    ],
    '+fefilter' => [
        'label',
        'template',
        'ynmode',
    ]
];
