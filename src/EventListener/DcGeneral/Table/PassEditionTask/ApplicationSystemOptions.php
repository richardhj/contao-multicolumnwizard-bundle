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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\PassEditionTask;


use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use Doctrine\DBAL\Connection;
use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem;
use Richardhj\ContaoFerienpassBundle\Model\Offer;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Class ApplicationSystemOptions
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\PassEditionTask
 */
class ApplicationSystemOptions
{

    /**
     * Doctrine.
     *
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * ApplicationSystemOptions constructor.
     *
     * @param ManagerRegistry $doctrine Doctrine.
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param GetPropertyOptionsEvent $event The event.
     */
    public function handle(GetPropertyOptionsEvent $event): void
    {
        if (('application_system' !== $event->getPropertyName())
            || ('tl_ferienpass_edition_task' !== $event->getModel()->getProviderName())
        ) {
            return;
        }

        $a = ApplicationSystem::findAll();

        $options = [];
        while ($a->next()) {
            $options[$a->type] = $a->type;
        }

        $event->setOptions($options);
    }
}
