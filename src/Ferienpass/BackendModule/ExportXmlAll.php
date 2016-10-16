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

use Contao\Controller;
use Contao\Environment;
use Contao\System;
use Ferienpass\Helper\DataProcessing;


class ExportXmlAll extends \BackendModule
{

    /**
     * Generate the module
     * @return string
     */
    public function generate()
    {
        System::loadLanguageFile('tl_ferienpass_exportXml');

        if (!\BackendUser::getInstance()->isAdmin) {
            return '<p class="tl_gerror">'.$GLOBALS['TL_LANG']['tl_ferienpass_exportXml']['permission'].'</p>';
        }

        return parent::generate();
    }


    /**
     * Generate the module
     */
    protected function compile()
    {
        // Output zip
        DataProcessing::exportXmlAll();

        // Redirect back
        Controller::redirect(str_replace('&mod=exportXmlAll', '', Environment::get('request')));
    }
}
