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

namespace Richardhj\ContaoFerienpassBundle\Helper;


use Richardhj\ContaoFerienpassBundle\ApplicationSystem\AbstractApplicationSystem;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\NoOp;


/**
 * Class Backend
 *
 * @package Richardhj\ContaoFerienpassBundle\Helper
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

        if (!$applicationSystem instanceof NoOp) {
            $name = $applicationSystem->getModel()->title;
            return sprintf('<p class="tl_info">Es läuft aktuell das Anmeldesystem <strong>%s</strong></p>', $name);

        } else {
            return '<p class="tl_error">Es läuft aktuell <strong>kein</strong> Anmeldesystem. Anmeldungen sind <strong>nicht möglich.</strong></p>';
        }
    }
}
