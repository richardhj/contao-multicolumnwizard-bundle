<?php
/**
 * E-POSTBUSINESS API integration for Contao Open Source CMS
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package E-POST
 * @author  Richard Henkenjohann <richard-epost@henkenjohann.me>
 */

namespace Ferienpass\ApplicationSystem;


use Ferienpass\Event\ChangeAttendanceStatusEvent;
use Ferienpass\Event\DeleteAttendanceEvent;
use Ferienpass\Event\SaveAttendanceEvent;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\AttendanceStatus;
use Ferienpass\Model\Config as FerienpassConfig;
use Symfony\Component\EventDispatcher\EventDispatcher;


class FirstCome extends AbstractApplicationSystem
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SaveAttendanceEvent::NAME   => [
                'updateAttendanceStatus',
            ],
            DeleteAttendanceEvent::NAME => [
                'updateAllStatusByOffer',
            ],
        ];
    }


    /**
     * Save the attendance status corresponding to the current application list
     *
     * @param SaveAttendanceEvent $event
     */
    public function updateAttendanceStatus(SaveAttendanceEvent $event)
    {
        global $container;
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $container['event-dispatcher'];

        $attendance = $event->getAttendance();
        $oldStatus = $attendance->getStatus();
        $newStatus = self::findStatusForAttendance($attendance);

        if ($newStatus->id === $oldStatus->id) {
            return;
        }

        $attendance->status = $newStatus->id;
        $attendance->save();

        $event = new ChangeAttendanceStatusEvent($attendance, $oldStatus, $newStatus);
        $dispatcher->dispatch(ChangeAttendanceStatusEvent::NAME, $event);

        \System::log(
            sprintf(
                'Status for attendance ID %u was changed from "%s" to "%s"',
                $attendance->id,
                $oldStatus->type,
                $attendance->getStatus()->type
            ),
            __METHOD__,
            TL_GENERAL
        );
    }


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
     * @param DeleteAttendanceEvent $event
     */
    public function updateAllStatusByOffer(DeleteAttendanceEvent $event)
    {
        $attendances = Attendance::findByOffer($event->getAttendance()->getOffer()->get('id'));

        // Stop if the last attendance was deleted
        if (null === $attendances) {
            return;
        }

        while ($attendances->next()) {
            $attendances->save();
        }
    }
}
