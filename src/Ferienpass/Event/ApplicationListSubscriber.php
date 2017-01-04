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

use Ferienpass\Helper\Message;
use Ferienpass\Helper\ToolboxOfferDate;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\Config as FerienpassConfig;
use Ferienpass\Model\Participant;
use Haste\DateTime\DateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class ApplicationListSubscriber
 * @package Ferienpass\Event
 */
class ApplicationListSubscriber implements EventSubscriberInterface
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
            BuildParticipantOptionsForApplicationListEvent::NAME => [
                ['disableAlreadyAttendingParticipants'],
                ['disableWrongAgeParticipants'],
            ],
            SaveAttendanceEvent::NAME                            => [
                'addAttendanceStatusMessage',
            ],
        ];
    }


    /**
     * Disable participants from options that are already attending
     *
     * @param BuildParticipantOptionsForApplicationListEvent $event
     */
    public function disableAlreadyAttendingParticipants(BuildParticipantOptionsForApplicationListEvent $event)
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
     * @param BuildParticipantOptionsForApplicationListEvent $event
     */
    public function disableWrongAgeParticipants(BuildParticipantOptionsForApplicationListEvent $event)
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
                    ->get(FerienpassConfig::getInstance()->participant_attribute_dateofbirth)
            );

            $age = $dateOfBirth->getAge($dateOffer);

            $isAgeAllowed = in_array(
                $event->getOffer()->get('id'),
                $event->getOffer()->getAttribute(FerienpassConfig::getInstance()->offer_attribute_age)->searchFor($age)
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
     * Display a message after saving a new attendance
     *
     * @param SaveAttendanceEvent $event
     */
    public function addAttendanceStatusMessage(SaveAttendanceEvent $event)
    {
        $participantName = $event
            ->getModel()
            ->getParticipant()
            ->parseAttribute(FerienpassConfig::getInstance()->participant_attribute_name)
        ['text'];

        $status = $event->getModel()->getStatus();

        Message::add(
            sprintf(
                $GLOBALS['TL_LANG']['MSC']['applicationList']['message'][$status->type],
                $participantName
            ),
            $status->messageType
        );
    }
}
