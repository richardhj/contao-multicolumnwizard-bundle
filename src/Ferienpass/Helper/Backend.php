<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Helper;


use Ferienpass\ApplicationSystem\AbstractApplicationSystem;


class Backend
{

    public function addCurrentApplicationSystemToSystemMessages()
    {
        global $container;

        /** @var AbstractApplicationSystem $applicationSystem */
        $applicationSystem = $container['ferienpass.applicationsystem'];
        $name = $applicationSystem->getModel()->type;

        $cssClass = (null !== $applicationSystem->getModel()) ? 'tl_info' : 'tl_warning';

        return sprintf('<p class="%s">Es läuft aktuell das Anmeldesystem <strong>%s</strong></p>', $cssClass,$name);
    }
}
