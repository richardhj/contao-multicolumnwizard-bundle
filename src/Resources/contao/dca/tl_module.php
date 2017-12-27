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
$GLOBALS['TL_DCA']['tl_module']['palettes']['host_logo'] = '{title_legend},name,headline,type;{config_legend},metamodel,hostLogoDir;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';

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

$GLOBALS['TL_DCA']['tl_module']['fields']['editFormTpl'] = [
    'label'            => &$GLOBALS['TL_LANG']['tl_module']['editFormTpl'],
    'exclude'          => true,
    'inputType'        => 'select',
    'options_callback' => ['tl_module_Ferienpass', 'getEditingTemplates'],
    'eval'             => [
        'tl_class' => 'w50',
    ],
    'sql'              => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['document'] = [
    'label'            => &$GLOBALS['TL_LANG']['tl_module']['document'],
    'exclude'          => true,
    'inputType'        => 'select',
    'options_callback' => ['Richardhj\ContaoFerienpassBundle\Helper\Dca', 'getDocumentChoices'],
    'eval'             => [
        'includeBlankOption' => true,
        'chosen'             => true,
        'tl_class'           => 'w50',
    ],
    'sql'              => "int(10) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['metamodel_child_list_view'] = [
    'label'            => &$GLOBALS['TL_LANG']['tl_content']['metamodel_child_list_view'],
    'exclude'          => true,
    'inputType'        => 'select',
    'options_callback' => ['Richardhj\ContaoFerienpassBundle\Helper\Dca', 'getAllMetaModelRenderSettings'],
    'default'          => '',
    'eval'             =>
        [
            'includeBlankOption' => true,
            'chosen'             => true,
            'tl_class'           => 'w50',
        ],
    'sql'              => "int(10) unsigned NOT NULL default '0'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['enableVariants'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['enableVariants'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'eval'      => [
        'tl_class' => 'w50',
    ],
    'sql'       => "char(1) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['hostLogoDir'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_module']['hostLogoDir'],
    'exclude'   => true,
    'inputType' => 'fileTree',
    'eval'      => [
        'fieldType' => 'radio',
        'mandatory' => true,
        'tl_class'  => 'clr',
    ],
    'sql'       => "binary(16) NULL",
];


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
