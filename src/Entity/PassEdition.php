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

use Doctrine\ORM\Mapping as ORM;

/**
 * Class PassEdition
 *
 * @ORM\Entity
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
     * @var int
     * @ORM\Column(type="integer")
     */
    private $holidayBegin;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $holidayEnd;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $hostEditEnd;

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
     * @return int
     */
    public function getHolidayBegin(): int
    {
        return $this->holidayBegin;
    }

    /**
     * @param int $holidayBegin
     */
    public function setHolidayBegin($holidayBegin): void
    {
        $this->holidayBegin = $holidayBegin;
    }

    /**
     * @return int
     */
    public function getHolidayEnd(): int
    {
        return $this->holidayEnd;
    }

    /**
     * @param int $holidayEnd
     */
    public function setHolidayEnd($holidayEnd): void
    {
        $this->holidayEnd = $holidayEnd;
    }

    /**
     * @return int
     */
    public function getHostEditEnd(): int
    {
        return $this->hostEditEnd;
    }

    /**
     * @param int $hostEditEnd
     */
    public function setHostEditEnd($hostEditEnd): void
    {
        $this->hostEditEnd = $hostEditEnd;
    }
}
