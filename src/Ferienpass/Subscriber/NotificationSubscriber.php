<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Subscriber;

use Contao\Model\Event\PostSaveModelEvent;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\AttendanceStatus;
use MetaModels\IItem;
use NotificationCenter\Model\Notification;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class NotificationSubscriber
 *
 * @package Ferienpass\Event
 */
class NotificationSubscriber implements EventSubscriberInterface
{

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            PostSaveModelEvent::NAME => [
                ['sendNewAttendanceStatusNotification'],
                ['sendChangedAttendanceStatusNotification'],
            ],
        ];
    }


    /**
     * Send the corresponding notification if the attendance status is assigned newly
     *
     * @param PostSaveModelEvent $event
     */
    public function sendNewAttendanceStatusNotification(PostSaveModelEvent $event)
    {
        /** @var Attendance $attendance */
        $attendance = $event->getModel();
        $attendance->refresh(); // TODO: check why we need to refresh
        /** @var Attendance $originalAttendance */
        $originalAttendance = $event->getOriginalModel();

        //TODO Cannot use getStatus() because of cache shit
        $originalStatus = AttendanceStatus::findByPk($originalAttendance->status);
        $currentStatus = AttendanceStatus::findByPk($attendance->status);

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


    /**
     * Send the corresponding notification if the attendance status was changed
     *
     * @param PostSaveModelEvent $event
     */
    public function sendChangedAttendanceStatusNotification(PostSaveModelEvent $event)
    {
        /** @var Attendance $attendance */
        $attendance = $event->getModel();
        $attendance->refresh(); // TODO: check why we need to refresh
        /** @var Attendance $originalAttendance */
        $originalAttendance = $event->getOriginalModel();

        //TODO Cannot use getStatus() because of cache shit
        $originalStatus = AttendanceStatus::findByPk($originalAttendance->status);
        $currentStatus = AttendanceStatus::findByPk($attendance->status);

        if (!$attendance instanceof Attendance
            || null === $originalStatus
            || $originalStatus === $currentStatus
        ) {
            return;
        }

        /** @var Notification $notification */
        /** @noinspection PhpUndefinedMethodInspection */
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


    /**
     * Get notification tokens
     *
     * @param IItem $participant
     * @param IItem $offer
     *
     * @return array
     */
    private static function getNotificationTokens($participant, $offer)
    {
        $tokens = [];

        // Add all offer fields
        foreach ($offer->getMetaModel()->getAttributes() as $name => $attribute) {
            $tokens['offer_' . $name] = $offer->parseAttribute($name)['text'];
        }

        // Add all the participant fields
        foreach ($participant->getMetaModel()->getAttributes() as $name => $attribute) {
            $tokens['participant_' . $name] = $participant->parseAttribute($name)['text'];
        }

        // Add all the parent's member fields
        $ownerAttribute = $participant->getMetaModel()->getAttributeById(
            $participant->getMetaModel()->get('owner_attribute')
        );
        foreach ($participant->get($ownerAttribute->getColName()) as $k => $v) {
            $tokens['member_' . $k] = $v;
        }

        // Add the participant's email
        $tokens['participant_email'] = $tokens['participant_email'] ?: $tokens['member_email'];

        // Add the host's email
        $tokens['host_email'] = $offer->get($offer->getMetaModel()->get('owner_attribute'))['email'];

        // Add the admin's email
        $tokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];

        return $tokens;
    }
}
