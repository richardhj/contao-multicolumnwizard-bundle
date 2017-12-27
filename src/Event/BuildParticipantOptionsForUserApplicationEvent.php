<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
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

    const NAME = 'ferienpass.user-application.build-participant-options';


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
