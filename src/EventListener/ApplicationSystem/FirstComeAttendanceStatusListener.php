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
     */
    public function handle(PreSaveModelEvent $event)
    {
        if (!$this->applicationSystem instanceof FirstCome) {
            return;
        }

        $attendance = $event->getModel();
        if (!$attendance instanceof Attendance) {
            return;
        }

        $oldStatus = $attendance->getStatus();
        $newStatus = self::findStatusForAttendance($attendance);
        if ($newStatus->id === $oldStatus->id) {
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
    public function handleDcGeneral(PrePersistModelEvent $event)
    {
        if (!$this->applicationSystem instanceof FirstCome) {
            return;
        }
        if (Attendance::getTable() !== $event->getEnvironment()->getDataDefinition()->getName()) {
            return;
        }

        $model      = $event->getModel();
        $attendance = Attendance::findByPk($model->getId());
        $newStatus  = self::findStatusForAttendance($attendance);
        $oldStatus  = $attendance->getStatus();
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
    protected static function findStatusForAttendance(Attendance $attendance)
    {
        // Is current status locked?
        if (null !== $attendance->getStatus() && $attendance->getStatus()->locked) {
            return $attendance->getStatus();
        }

        // Attendances are not up to date because participant or offer might be deleted
        if (null === $attendance->getOffer() || null === $attendance->getParticipant()) {
            return AttendanceStatus::findError();
        }

        $max = $attendance->getOffer()->get('applicationlist_max');

        // Offers without usage of application list or without limit
        if (!$attendance->getOffer()->get('applicationlist_active') || !$max) {
            return AttendanceStatus::findConfirmed();
        }

        $position = $attendance->getPosition();

        if (null !== $position) {
            if ($position < $max) {
                return AttendanceStatus::findConfirmed();

            } else {
                return AttendanceStatus::findWaitlisted();
            }
        } // Attendance not saved yet
        else {
            if (Attendance::countParticipants($attendance->getOffer()->get('id')) < $max) {
                return AttendanceStatus::findConfirmed();

            } else {
                return AttendanceStatus::findWaitlisted();
            }
        }
    }

}
