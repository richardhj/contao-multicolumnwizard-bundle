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

namespace Richardhj\ContaoFerienpassBundle\EventListener\Notification;


use Contao\Model\Event\PostSaveModelEvent;
use NotificationCenter\Model\Notification;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;

/**
 * Class NewAttendanceStatusListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\Notification
 */
class NewAttendanceStatusListener
{

    use GetNotificationTokensTrait;

    /**
     * Send the corresponding notification if the attendance status is assigned newly.
     *
     * @param PostSaveModelEvent $event The event.
     */
    public function handle(PostSaveModelEvent $event): void
    {
        /** @var Attendance $attendance */
        $attendance = $event->getModel();
        $attendance->refresh(); // TODO: check why we need to refresh
        /** @var Attendance $originalAttendance */
        $originalAttendance = $event->getOriginalModel();

        //TODO Cannot use getStatus() because of cache shit
        $originalStatus = AttendanceStatus::findByPk($originalAttendance->status);
        $currentStatus  = AttendanceStatus::findByPk($attendance->status);

        if (!$attendance instanceof Attendance
            || null !== $originalStatus
            || null === $currentStatus
        ) {
            return;
        }

        /** @var Notification $notification */
        $notification = Notification::findByPk($currentStatus->notification_new);

        if (null !== $notification) {
            $notification->send(
                self::getNotificationTokens(
                    $attendance->getParticipant(),
                    $attendance->getOffer()
                )
            );
        }
    }
}
