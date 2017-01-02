<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */


/**
 * Palettes
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['items_editing_actions'] = '{title_legend},name,headline,type;{config_legend},metamodel;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['offer_editing'] = '{title_legend},name,headline,type;{config_legend},metamodel,enableVariants;{redirect_legend},jumpTo;{template_legend:hide},editFormTpl,tableless;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['calendar_offers'] = $GLOBALS['TL_DCA']['tl_module']['palettes']['calendar'];
$GLOBALS['TL_DCA']['tl_module']['palettes']['offer_user_application'] = '{title_legend},name,headline,type;{config_legend},metamodel;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['offer_applicationlisthost'] = '{title_legend},name,headline,type;{config_legend},metamodel,metamodel_rendersettings,metamodel_child_list_view,document;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['offer_addattendeehost'] = '{title_legend},name,headline,type;{config_legend},metamodel;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['offers_user_attendances'] = '{title_legend},name,headline,type;{config_legend},metamodel,jumpTo;{template_legend:hide},customTpl;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

/**
 * Fields
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['jumpToApplicationList'] = $GLOBALS['TL_DCA']['tl_module']['fields']['jumpTo'];
//$GLOBALS['TL_DCA']['tl_module']['fields']['metamodel_fetch_item'] = $GLOBALS['TL_DCA']['tl_module']['fields']['metamodel'];
//$GLOBALS['TL_DCA']['tl_module']['fields']['metamodel_fetch_item']['label'] = &$GLOBALS['TL_LANG']['tl_module']['metamodel_fetch_item'];
//$GLOBALS['TL_DCA']['tl_module']['fields']['metamodel_fetch_item']['sql'] = "int(10) unsigned NOT NULL default '0'";
$GLOBALS['TL_DCA']['tl_module']['fields']['jumpToApplicationList']['label'] = &$GLOBALS['TL_LANG']['tl_module']['jumpToApplicationList'];

\Controller::loadDataContainer('tl_content');
\Controller::loadLanguageFile('tl_content');

$GLOBALS['TL_DCA']['tl_module']['fields']['editFormTpl'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['editFormTpl'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('tl_module_Ferienpass', 'getEditingTemplates'),
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "varchar(64) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['document'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['document'],
	'exclude'                 => true,
	'inputType'               => 'select',
	'options_callback'        => array('Ferienpass\Helper\Dca', 'getDocumentChoices'),
	'eval'                    => array('includeBlankOption'=>true, 'chosen'=>true, 'tl_class'=>'w50'),
	'sql'                     => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['metamodel_child_list_view'] = array
(
	'label'            => &$GLOBALS['TL_LANG']['tl_content']['metamodel_child_list_view'],
	'exclude'          => true,
	'inputType'        => 'select',
	'options_callback' => array('Ferienpass\Helper\Dca', 'getAllMetaModelRenderSettings'),
	'default'          => '',
	'eval'             => array
	(
		'includeBlankOption' => true,
//		'submitOnChange'     => true,
		'chosen'             => true,
		'tl_class'           => 'w50'
	),
	'sql' => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_module']['fields']['enableVariants'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_module']['enableVariants'],
	'exclude'                 => true,
	'inputType'               => 'checkbox',
	'eval'                    => array('tl_class'=>'w50'),
	'sql'                     => "char(1) NOT NULL default ''"
);


/**
 * Class tl_module_Ferienpass
 * Provide miscellaneous methods used by the dca
 */
class tl_module_Ferienpass extends tl_module
{
	/**
	 * Return all edit form templates as array
	 * @return array
	 */
	public function getEditingTemplates()
	{
		return $this->getTemplateGroup('offer_editing_');
	}
}
