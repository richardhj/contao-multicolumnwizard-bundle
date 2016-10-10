<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */
use Ferienpass\Helper\Config;


/**
 * Ferienpass configuration
 */
$GLOBALS['TL_DCA']['tl_ferienpass_config'] = array
(

	// Config
    'config'       => array
	(
        'dataContainer' => 'General',
        'forceEdit'     => true,
	),

    'dca_config'   => array
    (
        'data_provider' => array
        (
            'default' => array
            (
                'class' => 'DcGeneral\Data\SingleModelDataProvider',
            ),
        ),
        'view'          => 'DcGeneral\Contao\View\Contao2BackendView\SingleModelView',
    ),

    // Metapalettes
    'metapalettes' => array
	(
		'default' => array
		(
			'models'       => array
			(
				Config::CONFIG_PREFIX . Config::OFFER_MODEL,
				Config::CONFIG_PREFIX . Config::PARTICIPANT_MODEL
			),
			'attributes'   => array
			(
				Config::CONFIG_PREFIX . Config::OFFER_ATTRIBUTE_NAME,
				Config::CONFIG_PREFIX . Config::OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE,
				Config::CONFIG_PREFIX . Config::OFFER_ATTRIBUTE_APPLICATIONLIST_MAX,
				Config::CONFIG_PREFIX . Config::PARTICIPANT_ATTRIBUTE_NAME,
				Config::CONFIG_PREFIX . Config::PARTICIPANT_ATTRIBUTE_DATEOFBIRTH,
				Config::CONFIG_PREFIX . Config::OFFER_ATTRIBUTE_AGE,
				Config::CONFIG_PREFIX . Config::OFFER_ATTRIBUTE_DATE_CHECK_AGE,
				Config::CONFIG_PREFIX . Config::PARTICIPANT_ATTRIBUTE_AGREEMENT_PHOTOS
			),
			'restrictions' => array
			(
				Config::CONFIG_PREFIX . Config::PARTICIPANT_ALLOWED_ZIP_CODES,
				Config::CONFIG_PREFIX . Config::PARTICIPANT_MAX_APPLICATIONS_PER_DAY,
				Config::CONFIG_PREFIX . Config::PARTICIPANT_REGISTRATION_REQUIRED_FIELDS
			),
			'data_processing' => array
			(
//				'test'
			)
		)
	),

	// Fields
    'fields'       => array
	(
		Config::CONFIG_PREFIX . Config::OFFER_MODEL                              => array
		(
			'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_config'][Config::OFFER_MODEL],
			'inputType'        => 'select',
			'options_callback' => array('Ferienpass\Helper\Dca', 'getMetaModels'),
			'eval'             => array
			(
				'mandatory' => true,
				'chosen'    => true,
				'tl_class'  => 'w50'
			),
		),
		Config::CONFIG_PREFIX . Config::PARTICIPANT_MODEL                        => array
		(
			'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_config'][Config::PARTICIPANT_MODEL],
			'inputType'        => 'select',
			'options_callback' => array('Ferienpass\Helper\Dca', 'getMetaModels'),
			'eval'             => array
			(
				'mandatory' => true,
				'chosen'    => true,
				'tl_class'  => 'w50'
			),
		),
		Config::CONFIG_PREFIX . Config::OFFER_ATTRIBUTE_NAME                     => array
		(
			'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_config'][Config::OFFER_ATTRIBUTE_NAME],
			'inputType'        => 'conditionalselect',
			'options_callback' => array('Ferienpass\Helper\Dca', 'getMetaModelsAttributes'),
			'eval'             => array
			(
				'mandatory'      => true,
				'conditionField' => Config::CONFIG_PREFIX . Config::OFFER_MODEL,
				'tl_class'       => 'w50'
			)
		),
		Config::CONFIG_PREFIX . Config::OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE   => array
		(
			'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_config'][Config::OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE],
			'inputType'        => 'conditionalselect',
			'options_callback' => array('Ferienpass\Helper\Dca', 'getMetaModelsAttributes'),
			'eval'             => array
			(
				'mandatory'                => true,
				'conditionField'           => Config::CONFIG_PREFIX . Config::OFFER_MODEL,
				'tl_class'                 => 'w50',
				'metamodel_attribute_type' => 'checkbox'
			),
			'save_callback'    => array(array('Ferienpass\Helper\Dca', 'checkMetaModelAttributeType'))
		),
		Config::CONFIG_PREFIX . Config::OFFER_ATTRIBUTE_APPLICATIONLIST_MAX      => array
		(
			'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_config'][Config::OFFER_ATTRIBUTE_APPLICATIONLIST_MAX],
			'inputType'        => 'conditionalselect',
			'options_callback' => array('Ferienpass\Helper\Dca', 'getMetaModelsAttributes'),
			'eval'             => array
			(
				'mandatory'                => true,
				'conditionField'           => Config::CONFIG_PREFIX . Config::OFFER_MODEL,
				'tl_class'                 => 'w50',
				'metamodel_attribute_type' => 'numeric'
			),
			'save_callback'    => array(array('Ferienpass\Helper\Dca', 'checkMetaModelAttributeType'))
		),
		Config::CONFIG_PREFIX . Config::PARTICIPANT_ATTRIBUTE_NAME               => array
		(
			'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_config'][Config::PARTICIPANT_ATTRIBUTE_NAME],
			'inputType'        => 'conditionalselect',
			'options_callback' => array('Ferienpass\Helper\Dca', 'getMetaModelsAttributes'),
			'eval'             => array
			(
				'mandatory'      => true,
				'conditionField' => Config::CONFIG_PREFIX . Config::PARTICIPANT_MODEL,
				'tl_class'       => 'w50'
			)
		),
		Config::CONFIG_PREFIX . Config::PARTICIPANT_ATTRIBUTE_DATEOFBIRTH        => array
		(
			'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_config'][Config::PARTICIPANT_ATTRIBUTE_DATEOFBIRTH],
			'inputType'        => 'conditionalselect',
			'options_callback' => array('Ferienpass\Helper\Dca', 'getMetaModelsAttributes'),
			'eval'             => array
			(
				'mandatory'                => true,
				'conditionField'           => Config::CONFIG_PREFIX . Config::PARTICIPANT_MODEL,
				'tl_class'                 => 'w50',
				'metamodel_attribute_type' => 'timestamp'
			),
			'save_callback'    => array(array('Ferienpass\Helper\Dca', 'checkMetaModelAttributeType'))
		),
		Config::CONFIG_PREFIX . Config::PARTICIPANT_ATTRIBUTE_AGREEMENT_PHOTOS   => array
		(
			'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_config'][Config::PARTICIPANT_ATTRIBUTE_AGREEMENT_PHOTOS],
			'inputType'        => 'conditionalselect',
			'options_callback' => array('Ferienpass\Helper\Dca', 'getMetaModelsAttributes'),
			'eval'             => array
			(
				'mandatory'                => true,
				'conditionField'           => Config::CONFIG_PREFIX . Config::PARTICIPANT_MODEL,
				'tl_class'                 => 'w50',
				'metamodel_attribute_type' => 'checkbox'
			),
			'save_callback'    => array(array('Ferienpass\Helper\Dca', 'checkMetaModelAttributeType'))
		),
		Config::CONFIG_PREFIX . Config::OFFER_ATTRIBUTE_AGE                      => array
		(
			'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_config'][Config::OFFER_ATTRIBUTE_AGE],
			'inputType'        => 'conditionalselect',
			'options_callback' => array('Ferienpass\Helper\Dca', 'getMetaModelsAttributes'),
			'eval'             => array
			(
				'mandatory'                => true,
				'conditionField'           => Config::CONFIG_PREFIX . Config::OFFER_MODEL,
				'tl_class'                 => 'w50',
				'metamodel_attribute_type' => 'age'
			),
			'save_callback'    => array(array('Ferienpass\Helper\Dca', 'checkMetaModelAttributeType'))
		),
		Config::CONFIG_PREFIX . Config::OFFER_ATTRIBUTE_DATE_CHECK_AGE           => array
		(
			'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_config'][Config::OFFER_ATTRIBUTE_DATE_CHECK_AGE],
			'inputType'        => 'conditionalselect',
			'options_callback' => array('Ferienpass\Helper\Dca', 'getMetaModelsAttributes'),
			'eval'             => array
			(
				'mandatory'                => true,
				'conditionField'           => Config::CONFIG_PREFIX . Config::OFFER_MODEL,
				'tl_class'                 => 'w50',
				'metamodel_attribute_type' => 'timestamp'
			),
			'save_callback'    => array(array('Ferienpass\Helper\Dca', 'checkMetaModelAttributeType'))
		),
		Config::CONFIG_PREFIX . Config::PARTICIPANT_ALLOWED_ZIP_CODES            => array
		(
			'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_config'][Config::PARTICIPANT_ALLOWED_ZIP_CODES],
			'inputType' => 'text', //@todo we want a select/multicolumnfield with csv delimiter
			'eval'      => array
			(
				'tl_class' => 'w50',
			)
		),
		Config::CONFIG_PREFIX . Config::PARTICIPANT_MAX_APPLICATIONS_PER_DAY     => array
		(
			'label'     => &$GLOBALS['TL_LANG']['tl_ferienpass_config'][Config::PARTICIPANT_MAX_APPLICATIONS_PER_DAY],
			'inputType' => 'text',
			'eval'      => array
			(
				'tl_class' => 'w50',
				'rgxp'     => 'numeric'
			)
		),
		Config::CONFIG_PREFIX . Config::PARTICIPANT_REGISTRATION_REQUIRED_FIELDS => array
		(
			'label'            => &$GLOBALS['TL_LANG']['tl_ferienpass_config'][Config::PARTICIPANT_REGISTRATION_REQUIRED_FIELDS],
			'inputType'        => 'checkboxWizard',
			'options_callback' => array('Ferienpass\Helper\Dca', 'getEditableMemberProperties'),
			'eval'             => array('multiple' => true),
		)
	)
);
