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


use Contao\Model\Collection;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use NotificationCenter\Model\Notification;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceReminder;

class TriggerCronRemindersListener
{

    use GetNotificationTokensTrait;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * TriggerCronRemindersListener constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Check for attendance upcoming this day and trigger the reminder sending
     *
     * @internal param CronEvent $event
     */
    public function onCronHourly()
    {
        /** @var Collection|Attendance $reminders */
        $reminders = AttendanceReminder::findBy(['published=1'], []);
        if (null === $reminders) {
            return;
        }

        while ($reminders->next()) {
            $remindBefore = deserialize($reminders->remind_before);
            $time         = time();
            $timeEnd      = strtotime(sprintf('+%d %s', $remindBefore['value'], $remindBefore['unit']));
            $whereStatus  = ($reminders->attendance_status) ? ' AND status='.(int)$reminders->attendance_status : '';
            $attendances  = Attendance::findBy(
                [
                    "offer IN(SELECT id FROM mm_ferienpass WHERE published=1 AND id IN (SELECT item_id FROM tl_metamodel_offer_date WHERE start > {$time} AND start <= {$timeEnd}))"
                    ." AND id NOT IN (SELECT attendance FROM tl_ferienpass_attendance_notification WHERE tstamp<>0 AND notification=?)"
                    .$whereStatus,
                ],
                [$reminders->nc_notification]
            );

            if (null === $attendances) {
                return;
            }

            $this->sendAttendanceReminderNotifications($attendances, $reminders->nc_notification);
        }
    }

    /**
     * Send the attendance reminders. Trigger the notification for each attendance
     *
     * @param Attendance|Collection $attendances
     * @param int                   $notificationId
     */
    private function sendAttendanceReminderNotifications(Collection $attendances, int $notificationId)
    {
        /** @var Notification $notification */
        $notification = Notification::findByPk($notificationId);

        if (null !== $notification) {
            while ($attendances->next()) {
                $sent = $notification->send(
                    self::getNotificationTokens(
                        $attendances->current()->getParticipant(),
                        $attendances->current()->getOffer()
                    )
                );

                // Mark attendance notification as sent
                if (in_array(true, $sent)) {
                    $time = time();

                    try {
                        $this->connection->executeQuery(
                            "INSERT INTO tl_ferienpass_attendance_notification (tstamp, attendance, notification)".
                            " VALUES ({$time}, {$attendances->id}, {$notificationId})".
                            " ON DUPLICATE KEY UPDATE tstamp={$time}"
                        );
                    } catch (DBALException $e) {
                    }
                }
            }
        }
    }
}
