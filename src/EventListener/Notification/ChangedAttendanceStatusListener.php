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

namespace Richardhj\ContaoFerienpassBundle\EventListener\Notification;


use Contao\Model\Event\PostSaveModelEvent;
use NotificationCenter\Model\Notification;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\ApplicationSystemInterface;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\FirstCome;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;

class ChangedAttendanceStatusListener
{

    use GetNotificationTokensTrait;

    /**
     * @var ApplicationSystemInterface
     */
    private $applicationSystem;

    /**
     * ChangedAttendanceStatusListener constructor.
     *
     * @param ApplicationSystemInterface $applicationSystem
     */
    public function __construct(ApplicationSystemInterface $applicationSystem)
    {
        $this->applicationSystem = $applicationSystem;
    }

    /**
     * Send the corresponding notification if the attendance status was changed
     *
     * @param PostSaveModelEvent $event
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
            || null === $originalStatus
            || $originalStatus === $currentStatus
        ) {
            return;
        }

        // Only send notifications when application system is "first come"
        if (!($this->applicationSystem instanceof FirstCome)) {
            return;
        }

        /** @var Notification $notification */
        $notification = Notification::findByPk($currentStatus->notification_onChange);

        // Send the notification if one is set
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
