<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
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

    'dca_config'   => [
        'data_provider' => [
            'default' => [
                'class' => 'DcGeneral\Data\SingleModelDataProvider',
            ],
        ],
        'view'          => 'DcGeneral\Contao\View\Contao2BackendView\SingleModelView',
    ],

    // Metapalettes
    'metapalettes' => [
        'default' => [
            'models'          => [
                Config::OFFER_MODEL,
                Config::PARTICIPANT_MODEL,
            ],
            'attributes'      => [
                Config::OFFER_ATTRIBUTE_NAME,
                Config::OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE,
                Config::OFFER_ATTRIBUTE_APPLICATIONLIST_MAX,
                Config::PARTICIPANT_ATTRIBUTE_NAME,
                Config::PARTICIPANT_ATTRIBUTE_DATEOFBIRTH,
                Config::OFFER_ATTRIBUTE_AGE,
                Config::OFFER_ATTRIBUTE_DATE_CHECK_AGE,
                Config::PARTICIPANT_ATTRIBUTE_AGREEMENT_PHOTOS,
            ],
            'restrictions'    => [
                Config::PARTICIPANT_ALLOWED_ZIP_CODES,
                Config::PARTICIPANT_MAX_APPLICATIONS_PER_DAY,
                Config::PARTICIPANT_REGISTRATION_REQUIRED_FIELDS,
            ],
            'data_processing' => [],
        ],
    ],

    // Fields
    'fields'       => [
        Config::OFFER_MODEL                              => [
            'label'            => &$GLOBALS['TL_LANG'][$table][Config::OFFER_MODEL],
            'inputType'        => 'select',
            'options_callback' => ['Ferienpass\Helper\Dca', 'getMetaModels'],
            'eval'             => [
                'mandatory' => true,
                'chosen'    => true,
                'tl_class'  => 'w50',
            ],
        ],
        Config::PARTICIPANT_MODEL                        => [
            'label'            => &$GLOBALS['TL_LANG'][$table][Config::PARTICIPANT_MODEL],
            'inputType'        => 'select',
            'options_callback' => ['Ferienpass\Helper\Dca', 'getMetaModels'],
            'eval'             => [
                'mandatory' => true,
                'chosen'    => true,
                'tl_class'  => 'w50',
            ],
        ],
        Config::OFFER_ATTRIBUTE_NAME                     => [
            'label'            => &$GLOBALS['TL_LANG'][$table][Config::OFFER_ATTRIBUTE_NAME],
            'inputType'        => 'conditionalselect',
            'options_callback' => ['Ferienpass\Helper\Dca', 'getMetaModelsAttributes'],
            'eval'             => [
                'mandatory'      => true,
                'conditionField' => Config::OFFER_MODEL,
                'tl_class'       => 'w50',
            ],
        ],
        Config::OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE   => [
            'label'            => &$GLOBALS['TL_LANG'][$table][Config::OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE],
            'inputType'        => 'conditionalselect',
            'options_callback' => ['Ferienpass\Helper\Dca', 'getMetaModelsAttributes'],
            'eval'             => [
                'mandatory'                => true,
                'conditionField'           => Config::OFFER_MODEL,
                'tl_class'                 => 'w50',
                'metamodel_attribute_type' => 'checkbox',
            ],
            'save_callback'    => [['Ferienpass\Helper\Dca', 'checkMetaModelAttributeType']],
        ],
        Config::OFFER_ATTRIBUTE_APPLICATIONLIST_MAX      => [
            'label'            => &$GLOBALS['TL_LANG'][$table][Config::OFFER_ATTRIBUTE_APPLICATIONLIST_MAX],
            'inputType'        => 'conditionalselect',
            'options_callback' => ['Ferienpass\Helper\Dca', 'getMetaModelsAttributes'],
            'eval'             => [
                'mandatory'                => true,
                'conditionField'           => Config::OFFER_MODEL,
                'tl_class'                 => 'w50',
                'metamodel_attribute_type' => 'numeric',
            ],
            'save_callback'    => [['Ferienpass\Helper\Dca', 'checkMetaModelAttributeType']],
        ],
        Config::PARTICIPANT_ATTRIBUTE_NAME               => [
            'label'            => &$GLOBALS['TL_LANG'][$table][Config::PARTICIPANT_ATTRIBUTE_NAME],
            'inputType'        => 'conditionalselect',
            'options_callback' => ['Ferienpass\Helper\Dca', 'getMetaModelsAttributes'],
            'eval'             => [
                'mandatory'      => true,
                'conditionField' => Config::PARTICIPANT_MODEL,
                'tl_class'       => 'w50',
            ],
        ],
        Config::PARTICIPANT_ATTRIBUTE_DATEOFBIRTH        => [
            'label'            => &$GLOBALS['TL_LANG'][$table][Config::PARTICIPANT_ATTRIBUTE_DATEOFBIRTH],
            'inputType'        => 'conditionalselect',
            'options_callback' => ['Ferienpass\Helper\Dca', 'getMetaModelsAttributes'],
            'eval'             => [
                'mandatory'                => true,
                'conditionField'           => Config::PARTICIPANT_MODEL,
                'tl_class'                 => 'w50',
                'metamodel_attribute_type' => 'timestamp',
            ],
            'save_callback'    => [['Ferienpass\Helper\Dca', 'checkMetaModelAttributeType']],
        ],
        Config::PARTICIPANT_ATTRIBUTE_AGREEMENT_PHOTOS   => [
            'label'            => &$GLOBALS['TL_LANG'][$table][Config::PARTICIPANT_ATTRIBUTE_AGREEMENT_PHOTOS],
            'inputType'        => 'conditionalselect',
            'options_callback' => ['Ferienpass\Helper\Dca', 'getMetaModelsAttributes'],
            'eval'             => [
                'mandatory'                => true,
                'conditionField'           => Config::PARTICIPANT_MODEL,
                'tl_class'                 => 'w50',
                'metamodel_attribute_type' => 'checkbox',
            ],
            'save_callback'    => [['Ferienpass\Helper\Dca', 'checkMetaModelAttributeType']],
        ],
        Config::OFFER_ATTRIBUTE_AGE                      => [
            'label'            => &$GLOBALS['TL_LANG'][$table][Config::OFFER_ATTRIBUTE_AGE],
            'inputType'        => 'conditionalselect',
            'options_callback' => ['Ferienpass\Helper\Dca', 'getMetaModelsAttributes'],
            'eval'             => [
                'mandatory'                => true,
                'conditionField'           => Config::OFFER_MODEL,
                'tl_class'                 => 'w50',
                'metamodel_attribute_type' => 'age',
            ],
            'save_callback'    => [['Ferienpass\Helper\Dca', 'checkMetaModelAttributeType']],
        ],
        Config::OFFER_ATTRIBUTE_DATE_CHECK_AGE           => [
            'label'            => &$GLOBALS['TL_LANG'][$table][Config::OFFER_ATTRIBUTE_DATE_CHECK_AGE],
            'inputType'        => 'conditionalselect',
            'options_callback' => ['Ferienpass\Helper\Dca', 'getMetaModelsAttributes'],
            'eval'             => [
                'mandatory'                => true,
                'conditionField'           => Config::OFFER_MODEL,
                'tl_class'                 => 'w50',
                'metamodel_attribute_type' => 'timestamp',
            ],
            'save_callback'    => [['Ferienpass\Helper\Dca', 'checkMetaModelAttributeType']],
        ],
        Config::PARTICIPANT_ALLOWED_ZIP_CODES            => [
            'label'     => &$GLOBALS['TL_LANG'][$table][Config::PARTICIPANT_ALLOWED_ZIP_CODES],
            'inputType' => 'text', //@todo we want a select/multicolumnfield with csv delimiter
            'eval'      => [
                'tl_class' => 'w50',
            ],
        ],
        Config::PARTICIPANT_MAX_APPLICATIONS_PER_DAY     => [
            'label'     => &$GLOBALS['TL_LANG'][$table][Config::PARTICIPANT_MAX_APPLICATIONS_PER_DAY],
            'inputType' => 'text',
            'eval'      => [
                'tl_class' => 'w50',
                'rgxp'     => 'numeric',
            ],
        ],
        Config::PARTICIPANT_REGISTRATION_REQUIRED_FIELDS => [
            'label'            => &$GLOBALS['TL_LANG'][$table][Config::PARTICIPANT_REGISTRATION_REQUIRED_FIELDS],
            'inputType'        => 'checkboxWizard',
            'options_callback' => ['Ferienpass\Helper\Dca', 'getEditableMemberProperties'],
            'eval'             => [
                'multiple' => true,
                'csv'      => ',',
            ],
        ],
    ],
];
