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

namespace Richardhj\ContaoFerienpassBundle\Entity;

use Contao\System;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\ApplicationSystemInterface;
use Richardhj\ContaoFerienpassBundle\Exception\AmbiguousApplicationSystemException;
use Richardhj\ContaoFerienpassBundle\Exception\AmbiguousHolidayForPassEditionException;
use Richardhj\ContaoFerienpassBundle\Exception\MissingHolidayForPassEditionException;
use Richardhj\ContaoFerienpassBundle\Exception\MissingPayDaysForPassEditionException;

/**
 * Class PassEdition
 *
 * @ORM\Entity(repositoryClass="Richardhj\ContaoFerienpassBundle\Repository\PassEditionRepository")
 * @ORM\Table(name="tl_ferienpass_edition")
 * @package Richardhj\ContaoFerienpassBundle\Entity
 */
class PassEdition
{

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $tstamp;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $title;

    /**
     * @var PersistentCollection
     * @ORM\OneToMany(targetEntity="PassEditionTask", mappedBy="passEdition")
     * @ORM\OrderBy({"sorting"="ASC"})
     */
    private $tasks;

    /**
     * @var ApplicationSystemInterface
     */
    private $applicationSystem;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getTstamp(): int
    {
        return $this->tstamp;
    }

    /**
     * @param int $tstamp
     */
    public function setTstamp(int $tstamp): void
    {
        $this->tstamp = $tstamp;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return PersistentCollection
     */
    public function getTasks(): PersistentCollection
    {
        return $this->tasks;
    }

    /**
     * @param ArrayCollection $tasks
     */
    public function setTasks($tasks): void
    {
        $this->tasks = $tasks;
    }

    /**
     * Determine the current application system.
     *
     * @return ApplicationSystemInterface|null
     */
    public function getCurrentApplicationSystem(): ?ApplicationSystemInterface
    {
        if (null === $this->applicationSystem) {
            $time  = time();
            $tasks = $this->getTasks()->filter(
                function (PassEditionTask $element) use ($time) {
                    return 'application_system' === $element->getType()
                           && $time >= $element->getPeriodStart()
                           && $time < $element->getPeriodStop();
                }
            );

            if ($tasks->count() > 1) {
                throw new AmbiguousApplicationSystemException(
                    'More than one application system is applicable at the moment for pass edition ID ' . $this->getId()
                );
            }

            if ($tasks->isEmpty()) {
                return null;
            }

            $task = $tasks->current();

            /** @var ApplicationSystemInterface $applicationSystem */
            $this->applicationSystem = System::getContainer()->get(
                'richardhj.ferienpass.application_system.' . $task->getApplicationSystem()
            );

            $this->applicationSystem->setPassEditionTask($task);
        }

        return $this->applicationSystem;
    }

    /**
     * Get the holiday defined for this pass edition.
     *
     * @return PassEditionTask
     */
    public function getHoliday(): PassEditionTask
    {
        $tasks = $this->getTasks()->filter(
            function (PassEditionTask $element) {
                return 'holiday' === $element->getType();
            }
        );

        if ($tasks->count() > 1) {
            throw new AmbiguousHolidayForPassEditionException(
                'More than one holiday found for the pass edition ID ' . $this->getId()
            );
        }

        if ($tasks->isEmpty()) {
            throw new MissingHolidayForPassEditionException('No holiday found for pass edition ID ' . $this->getId());
        }

        return $tasks->current();
    }

    /**
     * Get the pay days task defined for this pass edition.
     *
     * @return Collection
     */
    public function getPayDays(): Collection
    {
        $tasks = $this->getTasks()->filter(
            function (PassEditionTask $element) {
                return 'pay_days' === $element->getType();
            }
        );

        if ($tasks->isEmpty()) {
            throw new MissingPayDaysForPassEditionException(
                'No pay days task found for pass edition ID ' . $this->getId()
            );
        }

        return $tasks;
    }

    /**
     * Get the host editing stages for this pass edition.
     *
     * @return Collection
     */
    public function getHostEditingStages(): Collection
    {
        $tasks = $this->getTasks()->filter(
            function (PassEditionTask $element) {
                return 'host_editing_stage' === $element->getType();
            }
        );

        return $tasks;
    }

    /**
     * Get the host editing stages for this pass edition.
     *
     * @return PassEditionTask|null
     */
    public function getCurrentHostEditingStage(): ?PassEditionTask
    {
        $time  = time();
        $tasks = $this->getTasks()->filter(
            function (PassEditionTask $element) use ($time) {
                return 'host_editing_stage' === $element->getType()
                       && $time >= $element->getPeriodStart()
                       && $time < $element->getPeriodStop();
            }
        );

        if ($tasks->count() > 1) {
            throw new \LogicException(
                'More than one host editing stage valid at the moment for pass edition ID' . $this->getId()
            );
        }

        if ($tasks->isEmpty()) {
            return null;
        }

        return $tasks->current();
    }

    /**
     * Get the host editing stages for this pass edition.
     *
     * @return PassEditionTask|null
     */
    public function getCurrentPayDays(): ?PassEditionTask
    {
        $time  = time();
        $tasks = $this->getTasks()->filter(
            function (PassEditionTask $element) use ($time) {
                return 'pay_days' === $element->getType()
                       && $time >= $element->getPeriodStart()
                       && $time < $element->getPeriodStop();
            }
        );

        if ($tasks->count() > 1) {
            throw new \LogicException(
                'More than one pay days tasks is valid at the moment for pass edition ID' . $this->getId()
            );
        }

        if ($tasks->isEmpty()) {
            return null;
        }

        return $tasks->current();
    }
}
