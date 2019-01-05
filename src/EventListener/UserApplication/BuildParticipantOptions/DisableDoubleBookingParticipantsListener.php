<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\EventListener\UserApplication\BuildParticipantOptions;


use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IItem;
use Richardhj\ContaoFerienpassBundle\Event\BuildParticipantOptionsForUserApplicationEvent;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

/**
 * Class DisableDoubleBookingParticipantsListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\UserApplication\BuildParticipantOptions
 */
class DisableDoubleBookingParticipantsListener
{

    /**
     * Disable participants from options that have an attendance for offer's date range already.
     *
     * @param BuildParticipantOptionsForUserApplicationEvent $event The event.
     *
     * @return void
     * @throws \RuntimeException
     */
    public function handle(BuildParticipantOptionsForUserApplicationEvent $event): void
    {
        $options    = $event->getResult();
        $offerDates = (array)$event->getOffer()->get('date_period');

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
                $participatingDatePeriods = array_map(
                    function (IItem $item) {
                        return $item->get('date_period');
                    },
                    iterator_to_array($participateOffers)
                );

                $participantDates = array_merge(...$participatingDatePeriods);
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
                        if (null === $overlappingOffer) {
                            throw new \RuntimeException(
                                'MetaModel item not found: '.ModelId::fromValues(
                                    $event->getOffer()->getMetaModel()->getTableName(),
                                    $participantDate['item_id']
                                )->getSerialized()
                            );
                        }

                        // Disable option
                        $options[$k]['disabled'] = true;
                        $options[$k]['label']    = sprintf(
                            $GLOBALS['TL_LANG']['MSC']['user_application']['participant']['option']['label']['double_booking'],
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
}