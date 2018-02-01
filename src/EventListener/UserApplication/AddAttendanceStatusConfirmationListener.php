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

namespace Richardhj\ContaoFerienpassBundle\EventListener\UserApplication;


use Contao\Model\Event\PostSaveModelEvent;
use Richardhj\ContaoFerienpassBundle\Helper\Message;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

class AddAttendanceStatusConfirmationListener
{

    /**
     * Display a message after saving a new attendance
     *
     * @param PostSaveModelEvent $event
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
                $GLOBALS['TL_LANG']['MSC']['applicationList']['message'][$status->type],
                $participantName
            ),
            $status->messageType
        );
    }
}
