<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
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
