<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\Helper;


use Contao\System;
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
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function addCurrentApplicationSystemToSystemMessages(): string
    {
        /** @var AbstractApplicationSystem $applicationSystem */
        $applicationSystem = System::getContainer()->get('richardhj.ferienpass.application_system');

        if (!$applicationSystem instanceof NoOp) {
            $name = $applicationSystem->getModel()->title;
            return sprintf('<p class="tl_info">Es läuft aktuell das Anmeldesystem <strong>%s</strong></p>', $name);

        }

        return '<p class="tl_error">Es läuft aktuell <strong>kein</strong> Anmeldesystem. Anmeldungen sind <strong>nicht möglich.</strong></p>';
    }
}
