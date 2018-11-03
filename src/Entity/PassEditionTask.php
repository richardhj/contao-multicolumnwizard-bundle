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
use Doctrine\ORM\Mapping as ORM;

/**
 * Class PassEditionTask
 *
 * @ORM\Entity
 * @ORM\Table(name="tl_ferienpass_edition_task")
 * @package Richardhj\ContaoFerienpassBundle\Entity
 */
class PassEditionTask
{

    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="PassEdition", inversedBy="tasks")
     * @ORM\JoinColumn(name="pid", referencedColumnName="id")
     */
    private $passEdition;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $tstamp;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $sorting;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $type;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $title;

    /**
     * @var int
     * @ORM\Column(name="max_applications", type="integer")
     */
    private $maxApplications;

    /**
     * @var int
     * @ORM\Column(name="period_start", type="integer")
     */
    private $periodStart;

    /**
     * @var int
     * @ORM\Column(name="period_stop", type="integer")
     */
    private $periodStop;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $color;

    /**
     * @var string
     * @ORM\Column(type="string")
     */
    private $description;

    /**
     * @var string
     * @ORM\Column(name="application_system", type="string")
     */
    private $applicationSystem;

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
     * @return mixed
     */
    public function getPassEdition()
    {
        return $this->passEdition;
    }

    /**
     * @param mixed $passEdition
     */
    public function setPassEdition($passEdition): void
    {
        $this->passEdition = $passEdition;
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
     * @return int
     */
    public function getSorting(): int
    {
        return $this->sorting;
    }

    /**
     * @param int $sorting
     */
    public function setSorting(int $sorting): void
    {
        $this->sorting = $sorting;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
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
     * @return int
     */
    public function getMaxApplications(): int
    {
        return $this->maxApplications;
    }

    /**
     * @param int $maxApplications
     */
    public function setMaxApplications(int $maxApplications): void
    {
        $this->maxApplications = $maxApplications;
    }

    /**
     * @return int
     */
    public function getPeriodStart(): int
    {
        return $this->periodStart;
    }

    /**
     * @param int $periodStart
     */
    public function setPeriodStart(int $periodStart): void
    {
        $this->periodStart = $periodStart;
    }

    /**
     * @return int
     */
    public function getPeriodStop(): int
    {
        return $this->periodStop;
    }

    /**
     * @param int $periodStop
     */
    public function setPeriodStop(int $periodStop): void
    {
        $this->periodStop = $periodStop;
    }

    /**
     * @return string
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * @param string $color
     */
    public function setColor(string $color): void
    {
        $this->color = $color;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getApplicationSystem(): string
    {
        return $this->applicationSystem;
    }

    /**
     * @param string $applicationSystem
     */
    public function setApplicationSystem(string $applicationSystem): void
    {
        $this->applicationSystem = $applicationSystem;
    }

    /**
     * @return string
     */
    public function getDisplayTitle(): string
    {
        $translator = System::getContainer()->get('translator');

        switch ($this->type) {
            case 'custom':
                return $this->getTitle();

            case 'application_system':
                return $translator->trans('MSC.application_system.' . $this->applicationSystem, [], 'contao_default');

            default:
                return $translator->trans(
                    'tl_ferienpass_edition_task.type_options.' . $this->type,
                    [],
                    'contao_tl_ferienpass_edition_task'
                );
        }
    }
}
