<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\ApplicationSystem;

use Contao\Model\Event\DeleteModelEvent;
use Contao\Model\Event\PreSaveModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PrePersistModelEvent;
use Ferienpass\Event\BuildParticipantOptionsForUserApplicationEvent;
use Ferienpass\Event\UserSetApplicationEvent;
use Ferienpass\Model\ApplicationSystem;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\AttendanceStatus;


/**
 * Class FirstCome
 *
 * @package Ferienpass\ApplicationSystem
 */
class FirstCome extends AbstractApplicationSystem
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            UserSetApplicationEvent::NAME                        => [
                'setNewAttendance',
            ],
            PreSaveModelEvent::NAME                              => [
                ['setAttendanceStatus'],
            ],
            PrePersistModelEvent::NAME                           => [
                ['setAttendanceStatusDcGeneral']
            ],
            DeleteModelEvent::NAME                               => [
                'updateAllStatusByOffer',
            ],
            BuildParticipantOptionsForUserApplicationEvent::NAME => [
                'disableLimitReachedParticipants',
            ],
        ];
    }


    public function setNewAttendance(UserSetApplicationEvent $event)
    {
        $this->setNewAttendanceInDatabase($event->getOffer(), $event->getParticipant());
    }


    /**
     * Save the attendance status corresponding to the current application list
     *
     * @param PreSaveModelEvent $event
     */
    public function setAttendanceStatus(PreSaveModelEvent $event)
    {
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
        $sorting        = (null !== $lastAttendance) ? $lastAttendance->sorting : 0;
        $sorting += 128;
        $data['sorting'] = $sorting;

        $event->setData($data);
    }


    /**
     * Save the attendance status corresponding to the current application list
     *
     * @param PrePersistModelEvent $event
     */
    public function setAttendanceStatusDcGeneral(PrePersistModelEvent $event)
    {
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
     * Find the status that matches the current attendance
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


    /**
     * Update all attendance statuses for one offer
     *
     * @param DeleteModelEvent $event
     */
    public function updateAllStatusByOffer(DeleteModelEvent $event)
    {
        $attendance = $event->getModel();
        if (!$attendance instanceof Attendance) {
            return;
        }

        Attendance::updateStatusByOffer($attendance->getOffer()->get('id'));
    }


    /**
     * Disable participants from options that have reached their limit
     *
     * @param BuildParticipantOptionsForUserApplicationEvent $event
     */
    public function disableLimitReachedParticipants(BuildParticipantOptionsForUserApplicationEvent $event)
    {
        $options               = $event->getResult();
        $maxApplicationsPerDay = ApplicationSystem::findFirstCome()->maxApplicationsPerDay;

        if (!$maxApplicationsPerDay) {
            return;
        }

        foreach ($options as $k => $option) {
            // Skip if already disabled
            if ($option['disabled']) {
                continue;
            }

            if (Attendance::countByParticipantAndDay($option['value']) >= $maxApplicationsPerDay) {
                $options[$k]['label']    = sprintf(
                    $GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label']['limit_reached'],
                    $option['label']
                );
                $options[$k]['disabled'] = true;
            }
        }

        $event->setResult($options);
    }
}
