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

use Contao\SelectMenu;
use Contao\System;


/**
 * Class ExportXml
 * @package Richardhj\ContaoFerienpassBundle\BackendModule
 */
class ExportXml extends \BackendModule
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'be_fp_exportXml';


    /**
     * Generate the module
     * @return string
     */
    public function generate()
    {
        System::loadLanguageFile('tl_ferienpass_exportXml');

        if (!\BackendUser::getInstance()->isAdmin) //@todo
        {
            return '<p class="tl_gerror">'.$GLOBALS['TL_LANG']['tl_ferienpass_exportXml']['permission'].'</p>';
        }

        return parent::generate();
    }


    /**
     * Generate the module
     */
    protected function compile()
    {
        //@todo select menu with order labels (red, blue, green) as options
        $objWidget = new SelectMenu(
            $this->prepareForWidget(
                $GLOBALS['TL_DCA']['tl_iso_orders']['fields']['recipient_select'],
                'recipient_select'
            )
        );


        $this->Template->action = \Environment::get('request');
        $this->Template->back = str_replace('&mod=exportXml', '', \Environment::get('request'));
    }
}
