<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Event;

use ContaoCommunityAlliance\DcGeneral\Event\PostPersistModelEvent;
use Ferienpass\ApplicationSystem\AbstractApplicationSystem;
use Ferienpass\Model\Attendance;
use NotificationCenter\Model\Notification;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class NotificationSubscriber
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
            PostPersistModelEvent::NAME => [
                ['sendNewAttendanceStatusNotification'],
                ['sendChangedAttendanceStatusNotification'],
            ],
        ];
    }


    /**
     * Send the corresponding notification if the attendance status is assigned newly
     *
     * @param PostPersistModelEvent $event
     */
    public function sendNewAttendanceStatusNotification(PostPersistModelEvent $event)
    {
        /** @var Attendance $attendance */
        $attendance = $event->getModel();
        /** @var Attendance $originalAttendance */
        $originalAttendance = $event->getOriginalModel();

        if (null !== $originalAttendance->getStatus() || null === $attendance->getStatus()) {
            return;
        }

        /** @var Notification $notification */
        $notification = Notification::findByPk($attendance->getStatus()->notification_new);

        if (null !== $notification) {
            global $container;

            $participant = $attendance->getParticipant();
            $offer = $attendance->getOffer();

            /** @var AbstractApplicationSystem $applicationSystem */
            $applicationSystem = $container['ferienpass.applicationsystem'];

            $notification->send($applicationSystem->getNotificationTokens($participant, $offer));
        }
    }


    /**
     * Send the corresponding notification if the attendance status was changed
     *
     * @param PostPersistModelEvent $event
     */
    public function sendChangedAttendanceStatusNotification(PostPersistModelEvent $event)
    {
        /** @var Attendance $attendance */
        $attendance = $event->getModel();
        /** @var Attendance $originalAttendance */
        $originalAttendance = $event->getOriginalModel();

        if (null === $originalAttendance->getStatus()
            || $originalAttendance->getStatus() === $attendance->getStatus()
        ) {
            return;
        }

        /** @var Notification $notification */
        /** @noinspection PhpUndefinedMethodInspection */
        $notification = Notification::findByPk($event->getAttendance()->getStatus()->notification_onChange);

        // Send the notification if one is set
        if (null !== $notification) {

            global $container;
            /** @var AbstractApplicationSystem $applicationSystem */
            $applicationSystem = $container['ferienpass.applicationsystem'];

            $notification->send(
                $applicationSystem->getNotificationTokens(
                    $attendance->getParticipant(),
                    $attendance->getOffer()
                )
            );
        }
    }
}
