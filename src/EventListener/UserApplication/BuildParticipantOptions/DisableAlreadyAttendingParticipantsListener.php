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


use Contao\FrontendUser;
use Richardhj\ContaoFerienpassBundle\Event\BuildParticipantOptionsForUserApplicationEvent;
use Richardhj\ContaoFerienpassBundle\Model\Participant;

/**
 * Class DisableAlreadyAttendingParticipantsListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\UserApplication\BuildParticipantOptions
 */
class DisableAlreadyAttendingParticipantsListener
{

    /**
     * The participants model.
     *
     * @var Participant
     */
    private $participantsModel;

    /**
     * DisableAlreadyAttendingParticipantsListener constructor.
     *
     * @param Participant $participantsModel The participants model.
     */
    public function __construct(Participant $participantsModel)
    {
        $this->participantsModel = $participantsModel;
    }

    /**
     * Disable participants from options that are already attending.
     *
     * @param BuildParticipantOptionsForUserApplicationEvent $event The event.
     *
     * @return void
     */
    public function handle(BuildParticipantOptionsForUserApplicationEvent $event): void
    {
        $options        = $event->getResult();
        $participantIds = $this->participantsModel
            ->byParentAndOfferFilter(FrontendUser::getInstance()->id, $event->getOffer()->get('id'))
            ->getMatchingIds();

        foreach ($options as $k => $option) {
            // Skip if already disabled
            if ($option['disabled']) {
                continue;
            }

            if (\in_array($option['value'], $participantIds, false)) {
                // Disable option
                $options[$k]['disabled'] = true;
                $options[$k]['label']    = sprintf(
                    $GLOBALS['TL_LANG']['MSC']['user_application']['participant']['option']['label']['already_attending'],
                    $option['label']
                );
            }
        }

        $event->setResult($options);
    }
}
