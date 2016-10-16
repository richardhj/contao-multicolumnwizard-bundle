<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\BackendModule;


class Management extends BackendOverview
{

    /**
     * {@inheritdoc}
     */
    protected function getModules()
    {
        $return = array();

        foreach ($GLOBALS['FERIENPASS_MOD'] as $strGroup => $arrModules) {
            foreach ($arrModules as $strModule => $arrConfig) {
                if ($this->checkUserAccess($strModule)) {
                    if (is_array($arrConfig['tables'])) {
                        $GLOBALS['BE_MOD']['ferienpass']['ferienpass_management']['tables'] += $arrConfig['tables'];
                    }

                    $return[$strGroup]['modules'][$strModule] = array_merge(
                        $arrConfig,
                        array
                        (
                            'label'       => specialchars($GLOBALS['TL_LANG']['FPMD'][$strModule][0] ?: $strModule),
                            'description' => specialchars(strip_tags($GLOBALS['TL_LANG']['FPMD'][$strModule][1])),
                            'href'        => TL_SCRIPT.'?do=ferienpass_management&mod='.$strModule,
                            'class'       => $arrConfig['class'],
                        )
                    );

                    $strLabel = str_replace(':hide', '', $strGroup);
                    $return[$strGroup]['label'] = $GLOBALS['TL_LANG']['FPMD'][$strLabel] ?: $strLabel;
                }
            }
        }

        return $return;
    }


    /**
     * {@inheritdoc}
     */
    protected function checkUserAccess($module)
    {
        return \BackendUser::getInstance()->isAdmin || \BackendUser::getInstance()->hasAccess($module, 'iso_modules');
    }


    /**
     * Generate the module
     */
    protected function compile()
    {
        $this->Template->before = '<h1 id="tl_welcome">'.$GLOBALS['TL_LANG']['FPMD']['management_module'].'</h1>';

        parent::compile();
    }
}
