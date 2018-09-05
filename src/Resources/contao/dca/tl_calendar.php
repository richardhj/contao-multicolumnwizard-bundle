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

use Richardhj\ContaoFerienpassBundle\EventListener\AddCalendarEventsListener;
use Richardhj\ContaoFerienpassBundle\Helper\Dca;


/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_calendar']['palettes']['__selector__'][]  = 'addMetamodel';
$GLOBALS['TL_DCA']['tl_calendar']['palettes']['default']         .= ';{metamodel_legend},addMetamodel';
$GLOBALS['TL_DCA']['tl_calendar']['subpalettes']['addMetamodel'] = 'metamodel,metamodelFields';


/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_calendar']['fields']['addMetamodel'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar']['addMetaModel'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => ['submitOnChange' => true, 'tl_class' => 'w50'],
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['metamodel'] = [
    'label'            => &$GLOBALS['TL_LANG']['tl_calendar']['metamodel'],
    'exclude'          => true,
    'inputType'        => 'select',
    'options_callback' => [Dca::class, 'getMetaModels'],
    'eval'             => ['tl_class' => 'w50'],
    'sql'              => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_calendar']['fields']['metamodelFields'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_calendar']['metamodelFields'],
    'exclude'   => true,
    'inputType' => 'multiColumnWizard',
    'eval'      =>
        [
            'columnFields' =>
                [
                    'calendar_field'  =>
                        [
                            'label'            => &$GLOBALS['TL_LANG']['tl_calendar']['mm_field_calendar'],
                            'exclude'          => true,
                            'inputType'        => 'select',
                            'options_callback' => [
                                AddCalendarEventsListener::class,
                                'getEventAttributesTranslated',
                            ],
                            'eval'             => ['style' => 'width:250px', 'chosen' => true],
                        ],
                    'metamodel_field' => [
                        'label'            => &$GLOBALS['TL_LANG']['tl_calendar']['mm_field_model'],
                        'exclude'          => true,
                        'inputType'        => 'conditionalselect',
                        'options_callback' => [Dca::class, 'getMetaModelsAttributes',],
                        'eval'             => [
                            'conditionField' => 'metamodel',
                            'chosen'         => true,
                            'style'          => 'width:250px',
                        ],
                    ],
                ],
        ],
    'sql'       => "text NULL",
];
