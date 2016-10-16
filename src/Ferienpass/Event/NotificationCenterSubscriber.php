<?php
/**
 * E-POSTBUSINESS API integration for Contao Open Source CMS
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package E-POST
 * @author  Richard Henkenjohann <richard-epost@henkenjohann.me>
 */

namespace Ferienpass\Event;


use NotificationCenter\Model\Notification;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class NotificationCenterSubscriber implements EventSubscriberInterface
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
            SaveAttendanceEvent::NAME         => [
                ['sendNewAttendanceStatusNotification'],
            ],
            ChangeAttendanceStatusEvent::NAME => [
                ['sendChangedAttendanceStatusNotification'],
            ],
        ];
    }


    public function sendNewAttendanceStatusNotification(SaveAttendanceEvent $event)
    {
        if (!$event->isNewModel()) {
            return;
        }

        /** @var Notification $notification */
        /** @noinspection PhpUndefinedMethodInspection */
        $notification = Notification::findByPk($event->getAttendance()->getStatus()->notification_new);

        if (null !== $notification) {
            $participant = $event->getAttendance()->getParticipant();
            $offer = $event->getAttendance()->getOffer();

            $notification->send($event->getAttendance()->getNotificationTokens($participant, $offer));
        }
    }


    public function sendChangedAttendanceStatusNotification(ChangeAttendanceStatusEvent $event)
    {
        /** @var Notification $notification */
        /** @noinspection PhpUndefinedMethodInspection */
        $notification = Notification::findByPk($event->getAttendance()->getStatus()->notification_onChange);

        // Send the notification if one is set
        if (null !== $notification) {
            $notification->send(
                $event->getAttendance()->getNotificationTokens(
                    $event->getAttendance()->getParticipant(),
                    $event->getAttendance()->getOffer()
                )
            );
        }
    }
}