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

namespace Richardhj\ContaoFerienpassBundle\HookListener;


use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\LogEvent;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\Participant;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class UserAccountListener
{

    /**
     * @var Participant
     */
    private $participantsModel;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Delete a member's participants and attendances
     *
     * @param integer $userId
     * @param string  $regClose
     *
     * @internal param \ModuleCloseAccount $module
     */
    public function onCloseAccount($userId, $regClose): void
    {
        if ('close_delete' !== $regClose) {
            return;
        }

        // Delete attendances
        $attendances = Attendance::findByParent($userId);
        $countAttendances = (null !== $attendances) ? $attendances->count() : 0;

        while (null !== $attendances && $attendances->next()) {
            $attendances->delete();
        }

        // Delete participants
        $participants = $this->participantsModel->findByParent($userId);
        $countParticipants = $participants->getCount();

        while ($participants->next()) {
            $this->participantsModel->getMetaModel()->delete($participants->getItem());
        }

        $this->dispatcher->dispatch(
            ContaoEvents::SYSTEM_LOG,
            new LogEvent(
                sprintf(
                    '%u participants and %u attendances for member ID %u has been deleted',
                    $countParticipants,
                    $countAttendances,
                    $userId
                ),
                __METHOD__,
                TL_GENERAL
            )
        );
    }
}
