<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\EventListener\HostEditing;

use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminatorAwareTrait;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetEditModeButtonsEvent;

/**
 * Class EditingButtonsListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\HostEditing
 */
class EditingButtonsListener
{

    use RequestScopeDeterminatorAwareTrait;

    /**
     * EditingButtonsListener constructor.
     *
     * @param RequestScopeDeterminator $scopeDeterminator The request mode determinator.
     */
    public function __construct(RequestScopeDeterminator $scopeDeterminator)
    {
        $this->setScopeDeterminator($scopeDeterminator);
    }

    /**
     * Remove the "save and create" button.
     *
     * @param GetEditModeButtonsEvent $event The event.
     */
    public function handleEvent(GetEditModeButtonsEvent $event): void
    {
        if (!$this->scopeDeterminator->currentScopeIsFrontend()) {
            return;
        }

        $buttons = $event->getButtons();
        unset($buttons['saveNcreate']);

        $event->setButtons($buttons);
    }
}
