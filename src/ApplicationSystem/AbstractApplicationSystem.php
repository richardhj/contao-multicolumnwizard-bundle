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

namespace Richardhj\ContaoFerienpassBundle\ApplicationSystem;


use Richardhj\ContaoFerienpassBundle\Entity\PassEditionTask;
use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem;

class AbstractApplicationSystem implements ApplicationSystemInterface
{

    /**
     * @var ApplicationSystem
     */
    private $model;

    /**
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
     * @return ApplicationSystem
     */
    public function getModel(): ApplicationSystem
    {
        return $this->model;
    }

    /**
     * @return PassEditionTask
     */
    public function getPassEditionTask(): ?PassEditionTask
    {
        return $this->passEditionTask;
    }

    /**
     * @param PassEditionTask $task
     */
    public function setPassEditionTask(PassEditionTask $task): void
    {
        $this->passEditionTask = $task;
    }
}
