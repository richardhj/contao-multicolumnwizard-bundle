<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2019 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2019 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\HookListener;

use Contao\Template;

/**
 * Class BackendLoginTemplateListener
 *
 * @package Richardhj\ContaoFerienpassBundle\HookListener
 */
class BackendLoginTemplateListener
{
    public function onParseTemplate(Template $template): void
    {
        if (false === strpos($template->getName(), 'be_login')) {
            return;
        }


        $template->stylesheets .= '<link rel="stylesheet" href="bundles/richardhjcontaoferienpass/css/be_login.css">';
    }
}
