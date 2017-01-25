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


/**
 * Class Backend
 *
 * @package Ferienpass\Helper
 */
class Backend
{

    /**
     * Display the current application system at the back end start page
     *
     * @return string
     */
    public function addCurrentApplicationSystemToSystemMessages()
    {
        global $container;

        /** @var AbstractApplicationSystem $applicationSystem */
        $applicationSystem = $container['ferienpass.applicationsystem'];

        if (null !== $applicationSystem) {
            $name = $applicationSystem->getModel()->title;
            return sprintf('<p class="tl_info">Es läuft aktuell das Anmeldesystem <strong>%s</strong></p>', $name);

        } else {
            return '<p class="tl_error">Es läuft aktuell <strong>kein</strong> Anmeldesystem. Anmeldungen sind <strong>nicht möglich</strong></p>';
        }
    }
}
