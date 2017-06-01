<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

use Ferienpass\Model\Config;


$table = Config::getTable();


/**
 * Ferienpass configuration
 */
$GLOBALS['TL_DCA'][$table] = [

    // Config
    'config' => [
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
            'restrictions'    => [
                'registrationAllowedZipCodes',
                'registrationRequiredFields',
            ],
            'data_processing' => [],
        ],
    ],

    // Fields
    'fields'       => [
        'registrationAllowedZipCodes'           => [
            'label'     => &$GLOBALS['TL_LANG'][$table]['registrationAllowedZipCodes'],
            'inputType' => 'text',
            'eval'      => [
                'tl_class' => 'w50',
            ],
        ],
        'registrationRequiredFields' => [
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
    ],
];
