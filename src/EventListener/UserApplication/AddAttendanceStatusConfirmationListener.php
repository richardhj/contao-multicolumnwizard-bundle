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

namespace Richardhj\ContaoFerienpassBundle\EventListener\UserApplication;


use Contao\Model\Event\PostSaveModelEvent;
use Richardhj\ContaoFerienpassBundle\Helper\Message;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

/**
 * Class AddAttendanceStatusConfirmationListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\UserApplication
 */
class AddAttendanceStatusConfirmationListener
{

    /**
     * Display a message after saving a new attendance.
     *
     * @param PostSaveModelEvent $event The event.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function handle(PostSaveModelEvent $event): void
    {
        $attendance = $event->getModel();
        if (!$attendance instanceof Attendance) {
            return;
        }

        $participant = $attendance->getParticipant();
        if (null === $participant) {
            throw new \RuntimeException('No participant given.');
        }

        $participantName = $participant->parseAttribute('name')['text'];

        $status = $attendance->getStatus();
        if (null === $status) {
            throw new \RuntimeException('No attendance status given.');
        }

        Message::add(
            sprintf(
                $GLOBALS['TL_LANG']['MSC']['user_application']['message'][$status->type],
                $participantName
            ),
            $status->messageType
        );
    }
}
