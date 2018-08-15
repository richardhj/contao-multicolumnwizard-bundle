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

namespace Richardhj\ContaoFerienpassBundle\Entity;

use Contao\System;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\PersistentCollection;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\ApplicationSystemInterface;
use Richardhj\ContaoFerienpassBundle\Exception\AmbiguousApplicationSystem;

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
                throw new AmbiguousApplicationSystem('More than one application system is applicable at the moment.');
            }

            if ($tasks->isEmpty()) {
                return null;
            }

            /** @var ApplicationSystemInterface $applicationSystem */
            $this->applicationSystem = System::getContainer()->get(
                'richardhj.ferienpass.application_system.' . $tasks->current()->get('application_system')
            );
        }

        return $this->applicationSystem;
    }
}
