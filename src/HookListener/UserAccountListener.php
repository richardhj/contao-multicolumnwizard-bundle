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

namespace Richardhj\ContaoFerienpassBundle\HookListener;


use Contao\CoreBundle\Monolog\ContaoContext;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\Participant;

/**
 * Class UserAccountListener
 *
 * @package Richardhj\ContaoFerienpassBundle\HookListener
 */
class UserAccountListener
{

    /**
     * The participant model.
     *
     * @var Participant
     */
    private $participantsModel;

    /**
     * The logger.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UserAccountListener constructor.
     *
     * @param Participant     $participantsModel The participant model.
     * @param LoggerInterface $logger            The logger.
     */
    public function __construct(Participant $participantsModel, LoggerInterface $logger)
    {
        $this->participantsModel = $participantsModel;
        $this->logger            = $logger;
    }

    /**
     * Delete a member's participants and attendances
     *
     * @param integer $userId   The user id.
     * @param string  $regClose The close account action.
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
        while (null !== $attendances && $attendances->next()) {
            $attendances->delete();
        }

        // Delete participants
        $participants = $this->participantsModel->findByParent($userId);
        while ($participants->next()) {
            $this->participantsModel->getMetaModel()->delete($participants->getItem());
        }

        $this->logger->log(
            LogLevel::INFO,
            sprintf(
                'Participants and attendances for member ID %u have been deleted',
                $userId
            ),
            ['contao' => new ContaoContext(__METHOD__, TL_GENERAL)]
        );
    }
}
