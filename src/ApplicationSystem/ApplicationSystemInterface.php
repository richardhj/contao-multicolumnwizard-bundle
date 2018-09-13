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
use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem;

/**
 * Interface ApplicationSystemInterface
 *
 * @package Richardhj\ContaoFerienpassBundle\ApplicationSystem
 */
interface ApplicationSystemInterface
{

    /**
     * Get the pass edition model.
     *
     * @return ApplicationSystem
     */
    public function getModel(): ApplicationSystem;

    /**
     * Get the pass edition task entity.
     *
     * @return PassEditionTask
     */
    public function getPassEditionTask(): ?PassEditionTask;

    /**
     * Set the pass edition task entity.
     *
     * @param PassEditionTask $task
     */
    public function setPassEditionTask(PassEditionTask $task);

    /**
     * Handle a new attendance.
     *
     * @param IItem $offer       The offer.
     * @param IItem $participant The participant.
     */
    public function setNewAttendance(IItem $offer, IItem $participant): void;
}
