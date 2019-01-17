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

namespace Richardhj\ContaoFerienpassBundle\EventListener\HostEditing;

use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminatorAwareTrait;
use ContaoCommunityAlliance\DcGeneral\Event\PostPersistModelEvent;
use MetaModels\DcGeneral\Data\Model;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class ConfirmationMessageListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\HostEditing
 */
class ConfirmationMessageListener
{

    use RequestScopeDeterminatorAwareTrait;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * EditHandler constructor.
     *
     * @param RequestScopeDeterminator $scopeDeterminator The request mode determinator.
     * @param SessionInterface         $session           The session.
     */
    public function __construct(RequestScopeDeterminator $scopeDeterminator, SessionInterface $session)
    {
        $this->setScopeDeterminator($scopeDeterminator);
        $this->session = $session;
    }

    /**
     * Add flash messages post persist.
     *
     * @param PostPersistModelEvent $event The event.
     */
    public function addFlashPostPersist(PostPersistModelEvent $event): void
    {


        //
        // This event listener is not enabled, as it does not work with the ajax request.
        // AjaxReloadElement does not trigger the FilterResponseEvent that is necessary to add the (foshttpcache) flashes.
        //


        $environment = $event->getEnvironment();
        /** @var Model $originalModel */
        $originalModel = $event->getOriginalModel();

        if (!($originalModel instanceof Model)
            || false === $this->scopeDeterminator->currentScopeIsFrontend()
            || 'mm_ferienpass' !== $environment->getDataDefinition()->getName()) {
            return;
        }

        $item = $originalModel->getItem();

        if (!$this->session instanceof Session) {
            return;
        }

        if ($item->isVariant()) {
            if (!$item->get('id')) {
                $message = 'Der zusätzliche Termin wurde erfolgreich angelegt.';
            } else {
                $message = 'Die Änderung des Termin wurde erfolgreich gespeichert.';
            }
        } elseif (!$item->get('id')) {
            $message = 'Das Angebot wurde erfolgreich neu erstellt.';
        } else {
            $message = 'Das Angebot wurde erfolgreich gespeichert.';
        }

        $this->session->getFlashBag()->add('confirmation', $message);
    }
}
