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


use Ferienpass\Helper\Message;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\Config as FerienpassConfig;
use Ferienpass\Model\Participant;
use Haste\DateTime\DateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


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
                'disableAlreadyAttendingParticipants',
                'disableWrongAgeParticipants',
                'disableLimitReachedParticipants',
            ],
            SaveAttendanceForApplicationListEvent::NAME          => [
                'addAttendanceStatusMessage',
            ],
        ];
    }


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


    public function disableWrongAgeParticipants(BuildParticipantOptionsForApplicationListEvent $event)
    {
        $options = $event->getResult();
        $dateOffer = new DateTime(
            '@'.$event
                ->getOffer()
                ->get(FerienpassConfig::getInstance()->offer_attribute_date_check_age)
        );

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


    public function disableLimitReachedParticipants(BuildParticipantOptionsForApplicationListEvent $event)
    {
        $options = $event->getResult();
        $maxApplicationsPerDay = FerienpassConfig::getInstance()->max_applications_per_day;

        if (!$maxApplicationsPerDay) {
            return;
        }

        foreach ($options as $k => $option) {
            $isLimitReached = Attendance::countByParticipantAndDay(
                Participant::getInstance()
                    ->findById($option['value'])
                    ->get('id')
            ) >= $maxApplicationsPerDay
                ? true
                : false;


            if ($isLimitReached) {
                $options[$k]['label'] = sprintf(
                    $GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label']['limit_reached'],
                    $option['label']
                );
                $options[$k]['disabled'] = true;
            }
        }

        $event->setResult($options);
    }


    public function addAttendanceStatusMessage(SaveAttendanceForApplicationListEvent $event)
    {
        $participantName = $event
            ->getParticipant()
            ->parseAttribute(FerienpassConfig::getInstance()->participant_attribute_name)
        ['text'];

        $status = $event->getAttendance()->getStatus();

        // Add message corresponding to attendance's status
        switch ($status->type) {
            case 'confirmed':
                Message::addConfirmation(
                    sprintf(
                        $GLOBALS['TL_LANG']['MSC']['applicationList']['message'][$status->type],
                        $participantName
                    )
                );
                break;

            case 'waiting':
                Message::addWarning(
                    sprintf(
                        $GLOBALS['TL_LANG']['MSC']['applicationList']['message'][$status->type],
                        $participantName
                    )
                );
                break;

            case 'error':
                Message::addError(
                    sprintf(
                        $GLOBALS['TL_LANG']['MSC']['applicationList']['message'][$status->type],
                        $participantName
                    )
                );
                break;
        }
    }
}
