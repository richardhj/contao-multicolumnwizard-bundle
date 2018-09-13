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

namespace Richardhj\ContaoFerienpassBundle\ApplicationSystem;


use MetaModels\IItem;
use Richardhj\ContaoFerienpassBundle\Entity\PassEditionTask;
use Richardhj\ContaoFerienpassBundle\Exception\DuplicatedAttendanceException;
use Richardhj\ContaoFerienpassBundle\Helper\ToolboxOfferDate;
use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

/**
 * Class AbstractApplicationSystem
 *
 * @package Richardhj\ContaoFerienpassBundle\ApplicationSystem
 */
class AbstractApplicationSystem implements ApplicationSystemInterface
{

    /**
     * The application system model.
     *
     * @var ApplicationSystem
     */
    private $model;

    /**
     * The pass edition task entity.
     *
     * @var PassEditionTask
     */
    private $passEditionTask;

    /**
     * AbstractApplicationSystem constructor.
     *
     * @param ApplicationSystem $model The model.
     */
    public function __construct(ApplicationSystem $model = null)
    {
        $this->model = $model;
    }

    /**
     * Get the pass edition model.
     *
     * @return ApplicationSystem
     */
    public function getModel(): ApplicationSystem
    {
        return $this->model;
    }

    /**
     * Get the pass edition task entity.
     *
     * @return PassEditionTask
     */
    public function getPassEditionTask(): ?PassEditionTask
    {
        return $this->passEditionTask;
    }

    /**
     * Set the pass edition task entity.
     *
     * @param PassEditionTask $task
     */
    public function setPassEditionTask(PassEditionTask $task): void
    {
        $this->passEditionTask = $task;
    }

    /**
     * Handle a new attendance.
     *
     * @param IItem $offer       The offer.
     * @param IItem $participant The participant.
     */
    public function setNewAttendance(IItem $offer, IItem $participant): void
    {
        $time = time();

        // Check if participant id allowed here and attendance not existent yet
        if (!Attendance::isNotExistent($participant->get('id'), $offer->get('id'))) {
            throw new DuplicatedAttendanceException(
                sprintf(
                    'Attendance for participant ID %s and offer ID %s already exists.',
                    $participant->get('id'),
                    $offer->get('id')
                )
            );
        }

        $attendance = new Attendance();

        $attendance->tstamp      = $time;
        $attendance->created     = $time;
        $attendance->offer       = $offer->get('id');
        $attendance->participant = $participant->get('id');

        $attendance->save();
    }

    /**
     * Delete an attendance.
     *
     * @param Attendance $attendance The attendance to delete.
     */
    public function deleteAttendance(Attendance $attendance): void
    {
        if (ToolboxOfferDate::offerStart($attendance->getOffer()) <= time()) {
            // Check for offer's date
            throw new \LogicException('The offer of the demanded attendance is in the past.');
        }

        $attendance->delete();
    }
}
