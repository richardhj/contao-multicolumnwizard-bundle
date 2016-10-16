<?php

namespace Ferienpass\Event;


use Ferienpass\Model\Attendance;
use Symfony\Component\EventDispatcher\Event;


class SaveAttendanceEvent extends Event
{

    const NAME = 'ferienpass.model.attendance.save';


    /**
     * @var Attendance
     */
    protected $attendance;


    /**
     * SaveAttendanceEvent constructor.
     *
     * @param Attendance $attendance
     */
    public function __construct(Attendance $attendance)
    {
        $this->attendance = $attendance;
    }


    /**
     * @return Attendance
     */
    public function getAttendance()
    {
        return $this->attendance;
    }
}
