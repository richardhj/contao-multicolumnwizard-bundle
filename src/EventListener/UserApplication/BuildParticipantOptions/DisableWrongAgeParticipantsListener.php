<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\EventListener\UserApplication\BuildParticipantOptions;


use Contao\Date;
use Haste\DateTime\DateTime;
use Richardhj\ContaoFerienpassBundle\Event\BuildParticipantOptionsForUserApplicationEvent;
use Richardhj\ContaoFerienpassBundle\Helper\ToolboxOfferDate;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class DisableWrongAgeParticipantsListener implements ContainerAwareInterface
{

    use ContainerAwareTrait;

    /**
     * Disable participants from options that have a wrong age.
     *
     * @param BuildParticipantOptionsForUserApplicationEvent $event The event.
     *
     * @return void
     * @throws \RuntimeException
     * @throws InvalidArgumentException
     */
    public function handle(BuildParticipantOptionsForUserApplicationEvent $event): void
    {
        if (null === ($offerStart = ToolboxOfferDate::offerStart($event->getOffer()))) {
            return;
        }

        $options       = $event->getResult();
        $dateTimeOffer = new DateTime('@'.$offerStart);

        foreach ($options as $k => $option) {
            // Skip if already disabled
            if ($option['disabled']) {
                continue;
            }

            $participant = $event
                ->getParticipants()
                ->reset()
                ->getItem()
                ->getMetaModel()
                ->findById($option['value']);
            if (null === $participant) {
                throw new \RuntimeException('Participant not found: ID '.$option['value']);
            }

            $dateTimeOfBirth = new DateTime('@'.$participant->get('dateOfBirth'));

            // Calculate age at offer's date
            $ageOnOffer           = $dateTimeOfBirth->getAge($dateTimeOffer);
            $offersWithAgeAllowed = [];

            switch ($this->container->getParameter('richardhj.ferienpass.age_check_method')) {
                case 'vague_on_year':
                    $dateOffer      = new Date($offerStart);
                    $ageOnYearBegin = $dateTimeOfBirth->getAge(new DateTime('@'.$dateOffer->yearBegin));
                    $ageOnYearEnd   = $dateTimeOfBirth->getAge(new DateTime('@'.$dateOffer->yearEnd));
                    foreach (array_unique([$ageOnOffer, $ageOnYearBegin, $ageOnYearEnd]) as $age) {
                        $offersWithAgeAllowed = array_unique(
                            array_merge(
                                $event->getOffer()->getAttribute('age')->searchFor($age),
                                $offersWithAgeAllowed
                            )
                        );
                    }
                    break;

                case 'exact':
                default:
                    $offersWithAgeAllowed = $event->getOffer()->getAttribute('age')->searchFor($ageOnOffer);
                    break;
            }

            $isAgeAllowed = \in_array(
                $event->getOffer()->get('id'),
                $offersWithAgeAllowed,
                true
            );

            if (!$isAgeAllowed) {
                // Disable option
                $options[$k]['disabled'] = true;
                $options[$k]['label']    = sprintf(
                    $GLOBALS['TL_LANG']['MSC']['user_application']['participant']['option']['label']['age_not_allowed'],
                    $option['label']
                );
            }
        }

        $event->setResult($options);
    }
}