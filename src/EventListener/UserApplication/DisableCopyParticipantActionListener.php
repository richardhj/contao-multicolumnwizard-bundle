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

namespace Richardhj\ContaoFerienpassBundle\EventListener\UserApplication;


use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use MetaModels\Events\ParseItemEvent;

class DisableCopyParticipantActionListener
{

    /**
     * @var RequestScopeDeterminator
     */
    private $scopeMatcher;

    /**
     * FrontendPermissionCheckListener constructor.
     *
     * @param RequestScopeDeterminator $scopeMatcher
     */
    public function __construct(RequestScopeDeterminator $scopeMatcher)
    {
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * @param ParseItemEvent $event The event.
     */
    public function handle(ParseItemEvent $event): void
    {
        if (false === $this->scopeMatcher->currentScopeIsFrontend()
            || $event->getItem()->getMetaModel()->getTableName() !== 'mm_participant') {
            return;
        }

        $result = $event->getResult();
        unset($result['actions']['copy']);

        $event->setResult($result);
    }
}
