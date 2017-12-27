<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\Subscriber;

use Contao\Date;
use Contao\Model\Event\PostSaveModelEvent;
use Richardhj\ContaoFerienpassBundle\Event\BuildParticipantOptionsForUserApplicationEvent as BuildOptionsEvent;
use Richardhj\ContaoFerienpassBundle\Helper\GetFerienpassConfigTrait;
use Richardhj\ContaoFerienpassBundle\Helper\Message;
use Richardhj\ContaoFerienpassBundle\Helper\ToolboxOfferDate;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\Participant;
use Haste\DateTime\DateTime;
use MetaModels\Filter\Rules\StaticIdList;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class UserApplicationSubscriber
 *
 * @package Richardhj\ContaoFerienpassBundle\Event
 */
class UserApplicationSubscriber implements EventSubscriberInterface
{

    use GetFerienpassConfigTrait;

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
            BuildOptionsEvent::NAME  => [
                ['disableAlreadyAttendingParticipants'],
                ['disableWrongAgeParticipants'],
                ['disableDoubleBookingParticipants'],
            ],
            PostSaveModelEvent::NAME => [
                'addAttendanceStatusMessage',
            ],
        ];
    }


    /**
     * Disable participants from options that are already attending
     *
     * @param BuildOptionsEvent $event
     */
    public function disableAlreadyAttendingParticipants(BuildOptionsEvent $event)
    {
        $options        = $event->getResult();
        $participantIds = Participant::getInstance()
            ->byParentAndOfferFilter(\FrontendUser::getInstance()->id, $event->getOffer()->get('id'))
            ->getMatchingIds();

        foreach ($options as $k => $option) {
            // Skip if already disabled
            if ($option['disabled']) {
                continue;
            }

            if (in_array($option['value'], $participantIds)) {
                // Disable option
                $options[$k]['disabled'] = true;
                $options[$k]['label']    = sprintf(
                    $GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label']['already_attending'],
                    $option['label']
                );
            }
        }

        $event->setResult($options);
    }


    /**
     * Disable participants from options that have a wrong age
     *
     * @param BuildOptionsEvent $event
     */
    public function disableWrongAgeParticipants(BuildOptionsEvent $event)
    {
        if (null === ($offerStart = ToolboxOfferDate::offerStart($event->getOffer()))) {
            return;
        }

        $options       = $event->getResult();
        $dateTimeOffer = new DateTime('@' . $offerStart);

        foreach ($options as $k => $option) {
            // Skip if already disabled
            if ($option['disabled']) {
                continue;
            }

            $dateTimeOfBirth = new DateTime(
                '@' . $event
                    ->getParticipants()
                    ->reset()
                    ->getItem()
                    ->getMetaModel()
                    ->findById($option['value'])
                    ->get('dateOfBirth')
            );

            // Calculate age at offer's date
            $ageOnOffer           = $dateTimeOfBirth->getAge($dateTimeOffer);
            $offersWithAgeAllowed = [];

            switch ($this->getFerienpassConfig()->getAgeCheckMethod()) {
                case 'vagueOnYear':
                    $dateOffer      = new Date($offerStart);
                    $ageOnYearBegin = $dateTimeOfBirth->getAge((new DateTime('@' . $dateOffer->yearBegin)));
                    $ageOnYearEnd   = $dateTimeOfBirth->getAge((new DateTime('@' . $dateOffer->yearEnd)));
                    foreach (array_unique([$ageOnOffer, $ageOnYearBegin, $ageOnYearEnd]) as $age) {
                        $offersWithAgeAllowed = array_unique(array_merge(
                            $event->getOffer()->getAttribute('age')->searchFor($age),
                            $offersWithAgeAllowed
                        ));
                    }
                    break;

                case 'exact':
                default:
                    $offersWithAgeAllowed = $event->getOffer()->getAttribute('age')->searchFor($ageOnOffer);
                    break;
            }

            $isAgeAllowed = in_array(
                $event->getOffer()->get('id'),
                $offersWithAgeAllowed
            );

            if (!$isAgeAllowed) {
                // Disable option
                $options[$k]['disabled'] = true;
                $options[$k]['label']    = sprintf(
                    $GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label']['age_not_allowed'],
                    $option['label']
                );
            }
        }

        $event->setResult($options);
    }


    /**
     * Disable participants from options that have an attendance for offer's date range already
     *
     * @param BuildOptionsEvent $event
     */
    public function disableDoubleBookingParticipants(BuildOptionsEvent $event)
    {
        $options    = $event->getResult();
        $offerDates = $event->getOffer()->get('date_period');

        foreach ($options as $k => $option) {
            // Skip if already disabled
            if ($option['disabled']) {
                continue;
            }

            $attendances = Attendance::findByParticipant($option['value']);

            // Fetch each offer the participant is already attending
            $participateOffers = null;
            if (null !== $attendances) {
                $participateOffers = $event
                    ->getOffer()
                    ->getMetaModel()
                    ->findByFilter(
                        $event
                            ->getOffer()
                            ->getMetaModel()
                            ->getEmptyFilter()
                            ->addFilterRule(new StaticIdList($attendances->fetchEach('offer')))
                    );
            }

            // Fetch all date periods the participant is already attending
            $participantDates = [];
            if (null !== $participateOffers) {
                while ($participateOffers->next()) {
                    $participantDates =
                        array_merge($participantDates, $participateOffers->getItem()->get('date_period'));
                }
            }

            // Walk every date the participant is already attending to…
            foreach ($participantDates as $participantDate) {
                foreach ($offerDates as $offerDate) {
                    // …check for an overlap
                    if (($offerDate['end'] >= $participantDate['start'])
                        && ($participantDate['end'] >= $offerDate['start'])
                    ) {
                        $overlappingOffer = $event
                            ->getOffer()
                            ->getMetaModel()
                            ->findById($participantDate['item_id']);

                        // Disable option
                        $options[$k]['disabled'] = true;
                        $options[$k]['label']    = sprintf(
                            $GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label']['double_booking'],
                            $option['label'],
                            $overlappingOffer->parseAttribute('name')['text']
                        );

                        break 2;
                    }
                }
            }
        }

        $event->setResult($options);
    }


    /**
     * Display a message after saving a new attendance
     *
     * @param PostSaveModelEvent $event
     */
    public function addAttendanceStatusMessage(PostSaveModelEvent $event)
    {
        $attendance = $event->getModel();
        if (!$attendance instanceof Attendance) {
            return;
        }

        $participantName = $attendance->getParticipant()->parseAttribute('name')['text'];
        $status          = $attendance->getStatus();

        Message::add(
            sprintf(
                $GLOBALS['TL_LANG']['MSC']['applicationList']['message'][$status->type],
                $participantName
            ),
            $status->messageType
        );
    }
}
