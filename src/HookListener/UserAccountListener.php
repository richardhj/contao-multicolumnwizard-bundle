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


use Contao\CoreBundle\Monolog\ContaoContext;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\Participant;

class UserAccountListener
{

    /**
     * @var Participant
     */
    private $participantsModel;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UserAccountListener constructor.
     *
     * @param Participant     $participantsModel
     * @param LoggerInterface $logger
     */
    public function __construct(Participant $participantsModel, LoggerInterface $logger)
    {
        $this->participantsModel = $participantsModel;
        $this->logger            = $logger;
    }

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
        $attendances      = Attendance::findByParent($userId);
        $countAttendances = (null !== $attendances) ? $attendances->count() : 0;

        while (null !== $attendances && $attendances->next()) {
            $attendances->delete();
        }

        // Delete participants
        $participants      = $this->participantsModel->findByParent($userId);
        $countParticipants = $participants->getCount();

        while ($participants->next()) {
            $this->participantsModel->getMetaModel()->delete($participants->getItem());
        }

        $this->logger->log(
            LogLevel::INFO,
            sprintf(
                '%u participants and %u attendances for member ID %u has been deleted',
                $countParticipants,
                $countAttendances,
                $userId
            ),
            ['contao' => new ContaoContext(__METHOD__, TL_GENERAL)]
        );
    }
}
