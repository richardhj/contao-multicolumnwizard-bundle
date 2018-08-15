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

namespace Richardhj\ContaoFerienpassBundle\EventListener\ApplicationSystem;


use Richardhj\ContaoFerienpassBundle\Event\UserSetApplicationEvent;
use Richardhj\ContaoFerienpassBundle\Helper\Message;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

class NewAttendanceListener extends AbstractApplicationSystemListener
{

    /**
     * Set a new attendance in the database.
     *
     * @param UserSetApplicationEvent $event
     *
     * @return void
     */
    public function handle(UserSetApplicationEvent $event): void
    {
        $offer = $event->getOffer();

        $applicationSystem = $this->offerModel->getApplicationSystem($offer);
        if (null === $applicationSystem) {
            Message::addError('Zurzeit sind keine Anmeldungen möglich. Bitte versuchen Sie es später.');

            return;
        }

        $time        = time();
        $participant = $event->getParticipant();

        // Check if participant id allowed here and attendance not existent yet
        if (Attendance::isNotExistent($participant->get('id'), $offer->get('id'))) {
            $attendance = new Attendance();

            $attendance->tstamp      = $time;
            $attendance->created     = $time;
            $attendance->offer       = $offer->get('id');
            $attendance->participant = $participant->get('id');
            $attendance->save();

        } // Attendance already exists
        else {
            Message::addError($GLOBALS['TL_LANG']['MSC']['applicationList']['error']);
        }
    }
}
