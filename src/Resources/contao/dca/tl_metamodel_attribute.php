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
 * Table tl_metamodel_attribute
 */
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['age extends _simpleattribute_']        = [];
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['offer_date extends _simpleattribute_'] = [];
$GLOBALS['TL_DCA']['tl_metamodel_attribute']['metapalettes']['alias_trigger_sync extends alias']     = [
    '+advanced' => [
        'data_processing',
    ],
];

//$GLOBALS['TL_DCA']['tl_metamodel_attribute']['fields']['data_processing'] = array
//(
//	'inputType'  => 'select',
//	'options_callback' => array('Richardhj\ContaoFerienpassBundle\Helper\Dca', 'getDataProcessingChoices'),
//	'eval' => array
//	(
//		'mandatory' => true
//	),
//	'sql'        => "int(10) NOT NULL default '0'"
//);
