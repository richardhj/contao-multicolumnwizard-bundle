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

namespace Richardhj\ContaoFerienpassBundle\EventListener\ApplicationSystem;


use Contao\Model\Event\DeleteModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PostDeleteModelEvent;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\FirstCome;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

/**
 * Class FirstComeDeletedAttendanceListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\ApplicationSystem
 */
class FirstComeDeletedAttendanceListener extends AbstractApplicationSystemListener
{

    /**
     * Update all attendance statuses for one offer.
     *
     * @param DeleteModelEvent $event The event.
     *
     * @return void
     */
    public function handle(DeleteModelEvent $event): void
    {
        $attendance = $event->getModel();
        if (!$attendance instanceof Attendance) {
            return;
        }

        $offer = $attendance->getOffer();
        if (null === $offer) {
            return;
        }

        $applicationSystem = $this->offerModel->getApplicationSystem($offer);
        if (!($applicationSystem instanceof FirstCome)) {
            return;
        }

        Attendance::updateStatusByOffer($offer->get('id'));
    }

    /**
     * Update all attendance statuses for one offer.
     *
     * @param PostDeleteModelEvent $event The event.
     *
     * @return void
     */
    public function handleDcGeneral(PostDeleteModelEvent $event): void
    {
        $environment = $event->getEnvironment();
        if ('tl_ferienpass_attendance' !== $environment->getDataDefinition()->getName()) {
            return;
        }

        $offerId = $event->getModel()->getProperty('offer');
        $offer   = $this->offerModel->findById($offerId);
        if (null === $offer) {
            return;
        }

        $applicationSystem = $this->offerModel->getApplicationSystem($offer);
        if (!($applicationSystem instanceof FirstCome)) {
            return;
        }

        Attendance::updateStatusByOffer($offerId);
    }
}
