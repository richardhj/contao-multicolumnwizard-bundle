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

namespace Richardhj\ContaoFerienpassBundle\HookListener;


use Richardhj\ContaoFerienpassBundle\ApplicationSystem\ApplicationSystemInterface;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\NoOp;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class SystemMessagesListener
{

    /**
     * @var ApplicationSystemInterface
     */
    private $applicationSystem;

    /**
     * SystemMessagesListener constructor.
     *
     * @param ApplicationSystemInterface $applicationSystem
     */
    public function __construct(ApplicationSystemInterface $applicationSystem)
    {
        $this->applicationSystem = $applicationSystem;
    }

    /**
     * Display the current application system at the back end start page
     *
     * @return string
     *
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    public function onGetSystemMessages(): string
    {
        if (!$this->applicationSystem instanceof NoOp) {
            $name = $this->applicationSystem->getModel()->title;

            return sprintf('<p class="tl_info">Es läuft aktuell das Anmeldesystem <strong>%s</strong></p>', $name);
        }

        return '<p class="tl_error">Es läuft aktuell <strong>kein</strong> Anmeldesystem. Anmeldungen sind <strong>nicht möglich.</strong></p>';
    }
}
