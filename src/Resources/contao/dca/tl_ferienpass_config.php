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

use Richardhj\ContaoFerienpassBundle\Model\Config;


$table = Config::getTable();


/**
 * Ferienpass configuration
 */
$GLOBALS['TL_DCA'][$table] = [

    // Config
    'config'       => [
        'dataContainer' => 'General',
        'forceEdit'     => true,
    ],

    // DCA config
    'dca_config'   => [
        'data_provider' => [
            'default' => [
                'class' => 'DcGeneral\Data\SingleModelDataProvider',
            ],
        ],
        'view'          => 'DcGeneral\View\SingleModelView',
    ],

    // Meta Palettes
    'metapalettes' => [
        'default' => [
            'restrictions' => [
                'registrationAllowedZipCodes',
                'registrationRequiredFields',
                'ageCheckMethod',
            ],
        ],
    ],

    // Fields
    'fields'       => [
        'registrationAllowedZipCodes' => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['registrationAllowedZipCodes'],
            'inputType' => 'text',
            'eval'      => [
                'tl_class' => 'w50',
            ],
        ],
        'registrationRequiredFields'  => [
            'label'            => &$GLOBALS['TL_LANG'][$table]['registrationRequiredFields'],
            'inputType'        => 'checkboxWizard',
            'options_callback' => function () {
                $return = [];

                \System::loadLanguageFile('tl_member');
                \Controller::loadDataContainer('tl_member');

                foreach ($GLOBALS['TL_DCA']['tl_member']['fields'] as $k => $v) {
                    if ($v['eval']['feEditable']) {
                        $return[$k] = $GLOBALS['TL_DCA']['tl_member']['fields'][$k]['label'][0];
                    }
                }

                return $return;
            },
            'eval'             => [
                'multiple' => true,
                'csv'      => ',',
            ],
        ],
        'ageCheckMethod'             => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['ageCheckMethod'],
            'inputType' => 'select',
            'default'   => 'exact',
            'reference' => &$GLOBALS['TL_LANG'][$table]['ageCheckMethodOptions'],
            'options'   => [
                'exact',
                'vagueOnYear',
            ],
            'eval'      => [
                'tl_class' => 'w50',
            ],
        ],
    ],
];
