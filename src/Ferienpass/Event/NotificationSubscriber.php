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

use Ferienpass\ApplicationSystem\AbstractApplicationSystem;
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
            SaveAttendanceEvent::NAME => [
                ['sendNewAttendanceStatusNotification'],
                ['sendChangedAttendanceStatusNotification'],
            ],
        ];
    }


    /**
     * Send the corresponding notification if the attendance status is assigned newly
     *
     * @param SaveAttendanceEvent $event
     */
    public function sendNewAttendanceStatusNotification(SaveAttendanceEvent $event)
    {
        if (null !== $event->getOriginalModel()->getStatus()) {
            return;
        }

        /** @var Notification $notification */
        $notification = Notification::findByPk($event->getModel()->getStatus()->notification_new);

        if (null !== $notification) {
            $participant = $event->getModel()->getParticipant();
            $offer = $event->getModel()->getOffer();

            global $container;
            /** @var AbstractApplicationSystem $applicationSystem */
            $applicationSystem = $container['ferienpass.applicationsystem'];

            $notification->send($applicationSystem->getNotificationTokens($participant, $offer));
        }
    }


    /**
     * Send the corresponding notification if the attendance status was changed
     *
     * @param SaveAttendanceEvent $event
     */
    public function sendChangedAttendanceStatusNotification(SaveAttendanceEvent $event)
    {
        if (null === $event->getOriginalModel()->getStatus()) {
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
                    $event->getModel()->getParticipant(),
                    $event->getModel()->getOffer()
                )
            );
        }
    }
}
