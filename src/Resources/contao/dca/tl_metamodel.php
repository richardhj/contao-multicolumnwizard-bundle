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

$GLOBALS['TL_DCA']['tl_metamodel']['metapalettes']['default extends default']['+advanced'][] = 'owner_attribute';

$GLOBALS['TL_DCA']['tl_metamodel']['fields']['owner_attribute'] = array
(
	'label'            => &$GLOBALS['TL_LANG']['tl_metamodel']['owner_attribute'],
	'exclude'          => true,
	'inputType'        => 'select',
	'options_callback' => array('Richardhj\ContaoFerienpassBundle\Helper\Dca', 'getOwnerAttributeChoices'),
	'eval'             => array
	(
		'includeBlankOption' => true,
		'tl_class'           => 'w50'
	),
	'sql'              => "int(10) unsigned NOT NULL default '0'"
);
