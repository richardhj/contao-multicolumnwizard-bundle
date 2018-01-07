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

namespace Richardhj\ContaoFerienpassBundle\Event;

use MetaModels\IItem;
use MetaModels\IItems;
use Symfony\Component\EventDispatcher\Event;


/**
 * Class BuildParticipantOptionsForUserApplicationEvent
 * @package Richardhj\ContaoFerienpassBundle\Event
 */
class BuildParticipantOptionsForUserApplicationEvent extends Event
{

    const NAME = 'richardhj.ferienpass.user-application.build-participant-options';


    /**
     * @var IItems
     */
    protected $participants;


    /**
     * @var IItem
     */
    protected $offer;


    /**
     * @var array
     */
    protected $result;


    /**
     * BuildParticipantOptionsForUserApplicationEvent constructor.
     *
     * @param IItems $participants
     * @param IItem  $offer
     * @param array  $result
     */
    public function __construct(IItems $participants, IItem $offer, array $result)
    {
        $this->participants = $participants;
        $this->offer = $offer;
        $this->result = $result;
    }


    /**
     * @return IItems
     */
    public function getParticipants()
    {
        return $this->participants;
    }


    /**
     * @return IItem
     */
    public function getOffer()
    {
        return $this->offer;
    }


    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }


    /**
     * @param array $result
     *
     * @return self
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }
}
