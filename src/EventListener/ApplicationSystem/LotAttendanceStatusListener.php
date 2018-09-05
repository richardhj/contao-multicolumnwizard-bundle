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


use Contao\Model\Event\PreSaveModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PrePersistModelEvent;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\Lot;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;

/**
 * Class LotAttendanceStatusListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\ApplicationSystem
 */
class LotAttendanceStatusListener extends AbstractApplicationSystemListener
{

    /**
     * Save the "waiting" status for one attendance per default.
     *
     * @param PreSaveModelEvent $event The event.
     *
     * @return void
     */
    public function handle(PreSaveModelEvent $event): void
    {
        /** @var Attendance $model */
        $model = $event->getModel();
        if (!$model instanceof Attendance || null !== $model->getStatus()) {
            return;
        }

        $offer = $model->getOffer();
        if (null === $offer) {
            return;
        }

        $applicationSystem = $this->offerModel->getApplicationSystem($offer);
        if (!($applicationSystem instanceof Lot)) {
            return;
        }

        $data = $event->getData();

        // Set status
        $data['status'] = AttendanceStatus::findWaiting()->id;

        // Update sorting afterwards
        $lastAttendance = Attendance::findLastByOfferAndStatus($model->offer, $data['status']);

        $sorting = (null !== $lastAttendance) ? $lastAttendance->sorting : 0;
        $sorting += 128;

        $data['sorting'] = $sorting;

        $event->setData($data);
    }


    /**
     * Save the "waiting" status for one attendance per default.
     *
     * @param PrePersistModelEvent $event The event.
     *
     * @return void
     */
    public function handleDcGeneral(PrePersistModelEvent $event): void
    {
        $environment = $event->getEnvironment();

        if ('tl_ferienpass_attendance' !== $environment->getDataDefinition()->getName()) {
            return;
        }

        $model = $event->getModel();
        if ($model->getProperty('status')) {
            return;
        }

        $offer = $this->offerModel->findById($model->getProperty('offer'));
        if (null === $offer) {
            return;
        }

        $applicationSystem = $this->offerModel->getApplicationSystem($offer);
        if (!($applicationSystem instanceof Lot)) {
            return;
        }

        // Set status
        $newStatus = AttendanceStatus::findWaiting();
        $model->setProperty('status', $newStatus->id);

        // Update sorting afterwards
        $lastAttendance = Attendance::findLastByOfferAndStatus(
            $model->getProperty('offer'),
            $model->getProperty('status')
        );

        $sorting = (null !== $lastAttendance) ? $lastAttendance->sorting : 0;
        $sorting += 128;
        $model->setProperty('sorting', $sorting);
    }
}
