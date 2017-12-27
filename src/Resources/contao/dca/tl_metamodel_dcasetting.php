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

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['metasubselectpalettes']['attr_id']['age'] = array
(
    'presentation' => array
    (
        'tl_class',
    ),
    'functions'    => array
    (
        'mandatory',
    ),
    'overview'     => array
    (
        'filterable',
        'searchable',
    )
);

$GLOBALS['TL_DCA']['tl_metamodel_dcasetting']['metasubselectpalettes']['attr_id']['offer_date'] = [
    'presentation' => [
        'tl_class',
    ],
    'functions'    => [
        'mandatory',
    ],
    'overview'     => [
        'filterable',
        'searchable',
    ],
];
