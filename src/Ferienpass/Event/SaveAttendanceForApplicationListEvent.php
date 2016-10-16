<?php

namespace Ferienpass\Event;


use Ferienpass\Model\Attendance;
use MetaModels\IItem;
use Symfony\Component\EventDispatcher\Event;


class SaveAttendanceForApplicationListEvent extends Event
{

    const NAME = 'ferienpass.application-list.save-attendance';


    /**
     * @var IItem
     */
    protected $participant;


    /**
     * @var IItem
     */
    protected $offer;


    /**
     * @var Attendance
     */
    private $attendance;


    /**
     * BuildParticipantOptionsForApplicationListEvent constructor.
     *
     * @param IItem      $participant
     * @param IItem      $offer
     * @param Attendance $attendance
     */
    public function __construct(IItem $participant, IItem $offer, Attendance $attendance)
    {
        $this->participant = $participant;
        $this->offer = $offer;
        $this->attendance = $attendance;
    }


    /**
     * @return IItem
     */
    public function getParticipant()
    {
        return $this->participant;
    }


    /**
     * @return IItem
     */
    public function getOffer()
    {
        return $this->offer;
    }


    /**
     * @return Attendance
     */
    public function getAttendance()
    {
        return $this->attendance;
    }
}
