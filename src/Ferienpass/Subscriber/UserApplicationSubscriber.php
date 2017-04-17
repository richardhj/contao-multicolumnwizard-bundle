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
use Ferienpass\Event\BuildParticipantOptionsForUserApplicationEvent as BuildOptionsEvent;
use Ferienpass\Helper\Message;
use Ferienpass\Helper\ToolboxOfferDate;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\Participant;
use Haste\DateTime\DateTime;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class UserApplicationSubscriber
 *
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
        $options = $event->getResult();

        $participantIds = Participant::getInstance()
            ->byParentAndOfferFilter(\FrontendUser::getInstance()->id, $event->getOffer()->get('id'))
            ->getMatchingIds();

        foreach ($options as $k => $option) {
            // Skip if already disabled
            if ($option['disabled']) {
                continue;
            }

            if (in_array($option['value'], $participantIds)) {
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

        $options   = $event->getResult();
        $dateOffer = new DateTime('@' . $offerStart);

        foreach ($options as $k => $option) {
            // Skip if already disabled
            if ($option['disabled']) {
                continue;
            }

            $dateOfBirth = new DateTime(
                '@' . $event
                    ->getParticipants()
                    ->getItem()
                    ->getMetaModel()
                    ->findById($option['value'])
                    ->get('dateOfBirth')
            );

            $age = $dateOfBirth->getAge($dateOffer);

            $isAgeAllowed = in_array(
                $event->getOffer()->get('id'),
                $event->getOffer()->getAttribute('age')->searchFor($age)
            );

            if (!$isAgeAllowed) {
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

            $attendances      = Attendance::findByParticipant($option['value']);
            $offers           = $event
                ->getOffer()
                ->getMetaModel()
                ->findByFilter(
                    $event
                        ->getOffer()
                        ->getMetaModel()
                        ->getEmptyFilter()
                        ->addFilterRule(new StaticIdList($attendances->fetchEach('offer')))
                );
            $participantDates = (null !== $offers) ? array_map(
                function ($offer) {
                    /** @var IItem $offer */
                    $offer->get('date_period');
                },
                iterator_to_array($offers)
            ) : [];

            foreach ($participantDates as $participantDate) {
                foreach ($offerDates as $offerDate) {
                    // Check for overlap
                    if (($offerDate['end'] >= $participantDate['start'])
                        && ($participantDate['end'] >= $offerDate['start'])
                    ) {
                        // Disable option
                        $options[$k]['disabled'] = true;
                        $options[$k]['label']    = sprintf(
                            $GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label']['double_booking'],
                            $option['label']
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
        /** @var Attendance $attendance */
        $attendance = $event->getModel();

        if (!$attendance instanceof Attendance) {
            return;
        }

        $participantName = $attendance->getParticipant()->parseAttribute('name')['text'];
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
