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

namespace Richardhj\ContaoFerienpassBundle\BackendModule;


/**
 * Class Management
 * @package Richardhj\ContaoFerienpassBundle\BackendModule
 */
class Management extends BackendOverview
{

    /**
     * {@inheritdoc}
     */
    protected function getModules()
    {
        $return = [];

        foreach ($GLOBALS['FERIENPASS_MOD'] as $strGroup => $arrModules) {
            foreach ($arrModules as $strModule => $arrConfig) {
                if ($this->checkUserAccess($strModule)) {
                    if (is_array($arrConfig['tables'])) {
                        $GLOBALS['BE_MOD']['ferienpass']['ferienpass_management']['tables'] += $arrConfig['tables'];
                    }

                    $return[$strGroup]['modules'][$strModule] = array_merge(
                        $arrConfig,
                        [
                            'label'       => specialchars($GLOBALS['TL_LANG']['FPMD'][$strModule][0] ?: $strModule),
                            'description' => specialchars(strip_tags($GLOBALS['TL_LANG']['FPMD'][$strModule][1])),
                            'href'        => TL_SCRIPT.'?do=ferienpass_management&mod='.$strModule,
                            'class'       => $arrConfig['class'],
                        ]
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
        /** @noinspection PhpParamsInspection */
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
