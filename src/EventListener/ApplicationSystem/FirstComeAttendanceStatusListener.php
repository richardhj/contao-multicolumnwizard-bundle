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

namespace Richardhj\ContaoFerienpassBundle\EventListener\ApplicationSystem;


use Contao\Model\Event\PreSaveModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PrePersistModelEvent;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\FirstCome;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;

class FirstComeAttendanceStatusListener extends AbstractApplicationSystemListener
{
    /**
     * Save the attendance status corresponding to the current application list.
     *
     * @param PreSaveModelEvent $event
     *
     * @return void
     * @throws \Exception
     */
    public function handle(PreSaveModelEvent $event): void
    {
        if (!$this->applicationSystem instanceof FirstCome) {
            return;
        }

        $attendance = $event->getModel();
        if (!$attendance instanceof Attendance) {
            return;
        }

        $newStatus = $this->findStatusForAttendance($attendance);
        $oldStatus = $attendance->getStatus();
        if (null !== $oldStatus && $newStatus->id === $oldStatus->id) {
            return;
        }

        $data = $event->getData();

        // Set sorting
        $data['status'] = $newStatus->id;
        $data['tstamp'] = time();

        // Update sorting afterwards
        $lastAttendance = Attendance::findLastByOfferAndStatus($attendance->offer, $data['status']);

        $sorting = (null !== $lastAttendance) ? $lastAttendance->sorting : 0;
        $sorting += 128;

        $data['sorting'] = $sorting;

        $event->setData($data);
    }

    /**
     * Save the attendance status corresponding to the current application list.
     *
     * @param PrePersistModelEvent $event
     *
     * @return void
     */
    public function handleDcGeneral(PrePersistModelEvent $event): void
    {
        $environment = $event->getEnvironment();
        if (!($this->applicationSystem instanceof FirstCome)
            || 'tl_ferienpass_attendance' !== $environment->getDataDefinition()->getName()) {
            return;
        }

        $model      = $event->getModel();
        $attendance = Attendance::findByPk($model->getId());
        $newStatus  = $this->findStatusForAttendance($attendance);
        $oldStatus  = $attendance->getStatus();
        if (null === $oldStatus) {
            throw new \RuntimeException('Old status not given.');
        }

        if ($newStatus->id === $oldStatus->id) {
            return;
        }

        // Set status
        $model->setProperty('status', $newStatus->id);

        // Update sorting afterwards
        $lastAttendance =
            Attendance::findLastByOfferAndStatus($model->getProperty('offer'), $model->getProperty('status'));

        $sorting = (null !== $lastAttendance) ? $lastAttendance->sorting : 0;
        $sorting += 128;
        $model->setProperty('sorting', $sorting);
    }


    /**
     * Find the status that matches the current attendance.
     *
     * @param Attendance $attendance
     *
     * @return AttendanceStatus
     */
    private function findStatusForAttendance(Attendance $attendance): AttendanceStatus
    {
        $currentStatus = $attendance->getStatus();
        $offer         = $attendance->getOffer();
        $participant   = $attendance->getParticipant();

        // Attendances are not up to date because participant or offer have been deleted
        if (null === $offer || null === $participant) {
            return AttendanceStatus::findError();
        }

        // Automatic assignment only for attendances being on waiting list
        if (null !== $currentStatus && 'waitlisted' !== $currentStatus->type) {
            return $currentStatus;
        }

        $max = $offer->get('applicationlist_max');

        // Offers without usage of application list or without limit
        if (!$max || !$offer->get('applicationlist_active')) {
            return AttendanceStatus::findConfirmed();
        }

        $position = $this->getPosition($attendance);
        if (null !== $position) {
            if ($position < $max) {
                return AttendanceStatus::findConfirmed();
            }

            return AttendanceStatus::findWaitlisted();
        }

        // Attendance not saved yet
        if (Attendance::countParticipants($offer->get('id')) < $max) {
            return AttendanceStatus::findConfirmed();
        }

        return AttendanceStatus::findWaitlisted();
    }

    /**
     * Get attendance's current position
     *
     * @param Attendance $attendance
     *
     * @return integer|null if participant not in attendance list (yet) or has error status
     */
    private function getPosition(Attendance $attendance): ?int
    {
        $attendances = Attendance::findByOffer($attendance->offer);
        if (null === $attendances) {
            return null;
        }

        for ($i = 0; $attendances->next(); $i++) {
            $status = $attendance->current()->getStatus();
            if (null === $status || ($status->type !== 'confirmed' && $status->type !== 'waitlisted')) {
                continue;
            }

            if ($attendances->current()->participant === $attendance->participant) {
                return $i;
            }
        }

        return null;
    }
}
