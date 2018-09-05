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


use Richardhj\ContaoFerienpassBundle\Entity\PassEditionTask;
use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem;

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
}
