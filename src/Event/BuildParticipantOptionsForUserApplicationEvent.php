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

namespace Richardhj\ContaoFerienpassBundle\Event;

use MetaModels\IItem;
use MetaModels\IItems;
use Symfony\Component\EventDispatcher\Event;


/**
 * Class BuildParticipantOptionsForUserApplicationEvent
 *
 * @package Richardhj\ContaoFerienpassBundle\Event
 */
class BuildParticipantOptionsForUserApplicationEvent extends Event
{

    public const NAME = 'richardhj.ferienpass.user-application.build-participant-options';

    /**
     * The participants.
     *
     * @var IItems
     */
    private $participants;

    /**
     * The offer.
     *
     * @var IItem
     */
    private $offer;

    /**
     * The widget options.
     *
     * @var array
     */
    private $result;

    /**
     * BuildParticipantOptionsForUserApplicationEvent constructor.
     *
     * @param IItems $participants The particpiants.
     * @param IItem  $offer        The offer.
     * @param array  $result       The widget options.
     */
    public function __construct(IItems $participants, IItem $offer, array $result)
    {
        $this->participants = $participants;
        $this->offer        = $offer;
        $this->result       = $result;
    }


    /**
     * @return IItems
     */
    public function getParticipants(): IItems
    {
        return $this->participants;
    }


    /**
     * @return IItem
     */
    public function getOffer(): IItem
    {
        return $this->offer;
    }


    /**
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }


    /**
     * @param array $result
     *
     * @return self
     */
    public function setResult($result): self
    {
        $this->result = $result;

        return $this;
    }
}
