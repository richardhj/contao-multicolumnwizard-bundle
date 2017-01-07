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
use Ferienpass\Event\BuildParticipantOptionsForApplicationListEvent;
use Ferienpass\Event\UserSetAttendanceEvent;
use Ferienpass\Model\ApplicationSystem;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\AttendanceStatus;
use Ferienpass\Model\Config as FerienpassConfig;
use Ferienpass\Model\Participant;


/**
 * Class FirstCome
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
            UserSetAttendanceEvent::NAME                         => [
                'setNewAttendance',
            ],
            PreSaveModelEvent::NAME                              => [
                ['setAttendanceStatus'],
            ],
            DeleteModelEvent::NAME                               => [
                'updateAllStatusByOffer',
            ],
            BuildParticipantOptionsForApplicationListEvent::NAME => [
                'disableLimitReachedParticipants',
            ],
        ];
    }


    public function setNewAttendance(UserSetAttendanceEvent $event)
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
        $data['status'] = $newStatus->id;
        $event->setData($data);
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

        // Offers without usage of application list or without limit
        if (!$attendance->getOffer()->get(FerienpassConfig::getInstance()->offer_attribute_applicationlist_active)
            || !($max = $attendance->getOffer()->get(
                FerienpassConfig::getInstance()->offer_attribute_applicationlist_max
            ))
        ) {
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

        $attendances = Attendance::findByOffer($attendance->getOffer()->get('id'));

        // Stop if the last attendance was deleted
        if (null === $attendances) {
            return;
        }

        // todo does this produces a loop overload?
        while ($attendances->next()) {
            $attendances->save();
        }
    }


    /**
     * Disable participants from options that have reached their limit
     *
     * @param BuildParticipantOptionsForApplicationListEvent $event
     */
    public function disableLimitReachedParticipants(BuildParticipantOptionsForApplicationListEvent $event)
    {
        $options = $event->getResult();
        $maxApplicationsPerDay = ApplicationSystem::findFirstCome()->maxApplicationsPerDay;

        if (!$maxApplicationsPerDay) {
            return;
        }

        foreach ($options as $k => $option) {
            $isLimitReached = Attendance::countByParticipantAndDay(
                Participant::getInstance()
                    ->findById($option['value'])
                    ->get('id')
            ) >= $maxApplicationsPerDay
                ? true
                : false;

            if ($isLimitReached) {
                $options[$k]['label'] = sprintf(
                    $GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label']['limit_reached'],
                    $option['label']
                );
                $options[$k]['disabled'] = true;
            }
        }

        $event->setResult($options);
    }
}
