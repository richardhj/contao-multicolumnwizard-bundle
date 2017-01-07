<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Event;

use Ferienpass\Model\Attendance;
use MetaModels\IItem;
use Symfony\Component\EventDispatcher\Event;


/**
 * Class UserSetAttendanceEvent
 * @package Ferienpass\Event
 */
class UserSetAttendanceEvent extends Event
{

    const NAME = 'ferienpass.application-list.set-attendance';


    /**
     * @var IItem
     */
    protected $offer;


    /**
     * @var IItem
     */
    protected $participant;


    /**
     * UserSetAttendanceEvent constructor.
     *
     * @param IItem $offer
     * @param IItem $participant
     */
    public function __construct(IItem $offer, IItem $participant)
    {
        $this->offer = $offer;
        $this->participant = $participant;
    }


    /**
     * @return IItem
     */
    public function getOffer()
    {
        return $this->offer;
    }


    /**
     * @return IItem
     */
    public function getParticipant()
    {
        return $this->participant;
    }
}
