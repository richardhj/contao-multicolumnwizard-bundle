<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Event;

use Contao\Model\Event\PostSaveModelEvent;
use Contao\Model\Event\PreSaveModelEvent;
use Ferienpass\Helper\Message;
use Ferienpass\Helper\ToolboxOfferDate;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\Participant;
use Haste\DateTime\DateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class UserApplicationSubscriber
 * @package Ferienpass\Event
 */
class UserApplicationSubscriber implements EventSubscriberInterface
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
            BuildParticipantOptionsForUserApplicationEvent::NAME => [
                ['disableAlreadyAttendingParticipants'],
                ['disableWrongAgeParticipants'],
                ['disableDoubleBookingParticipants'],
            ],
            PreSaveModelEvent::NAME                              => [
                'setSorting',
            ],
            PostSaveModelEvent::NAME                             => [
                'addAttendanceStatusMessage',
            ],
        ];
    }


    /**
     * Disable participants from options that are already attending
     *
     * @param BuildParticipantOptionsForUserApplicationEvent $event
     */
    public function disableAlreadyAttendingParticipants(BuildParticipantOptionsForUserApplicationEvent $event)
    {
        $options = $event->getResult();

        $participantIds = Participant::getInstance()
            ->byParentAndOfferFilter(\FrontendUser::getInstance()->id, $event->getOffer()->get('id'))
            ->getMatchingIds();

        foreach ($options as $k => $option) {
            if (in_array($option['value'], $participantIds)) {
                $options[$k]['label'] = sprintf(
                    $GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label']['already_attending'],
                    $option['label']
                );
                $options[$k]['disabled'] = true;
            }
        }

        $event->setResult($options);
    }


    /**
     * Disable participants from options that have a wrong age
     *
     * @param BuildParticipantOptionsForUserApplicationEvent $event
     */
    public function disableWrongAgeParticipants(BuildParticipantOptionsForUserApplicationEvent $event)
    {
        if (null === ($offerStart = ToolboxOfferDate::offerStart($event->getOffer()))) {
            return;
        }

        $options = $event->getResult();
        $dateOffer = new DateTime('@'.$offerStart);

        foreach ($options as $k => $option) {
            $dateOfBirth = new DateTime(
                '@'.Participant::getInstance()
                    ->findById($option['value'])
                    ->get('dateOfBirth')
            );

            $age = $dateOfBirth->getAge($dateOffer);

            $isAgeAllowed = in_array(
                $event->getOffer()->get('id'),
                $event->getOffer()->getAttribute('age')->searchFor($age)
            );

            if (!$isAgeAllowed) {
                $options[$k]['label'] = sprintf(
                    $GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label']['age_not_allowed'],
                    $option['label']
                );
                $options[$k]['disabled'] = true;
            }
        }

        $event->setResult($options);
    }


    /**
     * Disable participants from options that have an attendance for offer's date range already
     *
     * @param BuildParticipantOptionsForUserApplicationEvent $event
     */
    public function disableDoubleBookingParticipants(BuildParticipantOptionsForUserApplicationEvent $event)
    {
        $options = $event->getResult();

        $event->setResult($options);
    }


    /**
     * Set the sorting when saving an attendance made by the user
     *
     * @param PreSaveModelEvent $event
     */
    public function setSorting(PreSaveModelEvent $event)
    {
        $attendance = $event->getModel();

        if (!$attendance instanceof Attendance || $attendance->sorting) {
            return;
        }

        $lastAttendance = Attendance::findLastByOfferAndStatus($attendance->offer, $attendance->status);
        $sorting = (null !== $lastAttendance) ? $lastAttendance->sorting : 0;
        $sorting += 128;

        $data = $event->getData();
        $data['sorting'] = $sorting;
        $event->setData($data);
    }


    /**
     * Display a message after saving a new attendance
     *
     * @param PostSaveModelEvent $event
     */
    public function addAttendanceStatusMessage(PostSaveModelEvent $event)
    {
        /** @var Attendance $attendance */
        $attendance = $event->getModel();

        if (!$attendance instanceof Attendance) {
            return;
        }

        $participantName = $attendance
            ->getParticipant()
            ->parseAttribute('name')['text'];

        $status = $attendance->getStatus();

        Message::add(
            sprintf(
                $GLOBALS['TL_LANG']['MSC']['applicationList']['message'][$status->type],
                $participantName
            ),
            $status->messageType
        );
    }
}
