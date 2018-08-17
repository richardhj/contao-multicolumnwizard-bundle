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
    '+config' => ['attr_id', 'urlparam', 'label', 'template'],
];

// Pass edition
$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['metapalettes']['pass_edition extends simplelookup'] = [];

// Attendance available
$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['metapalettes']['attendance_available extends _attribute_']['+config'][] =
    'urlparam';

$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['metapalettes']['attendance_available extends _attribute_']['+fefilter'][] =
    'label';
$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['metapalettes']['attendance_available extends _attribute_']['+fefilter'][] =
    'template';
$GLOBALS['TL_DCA']['tl_metamodel_filtersetting']['metapalettes']['attendance_available extends _attribute_']['+fefilter'][] =
    'ynmode';
