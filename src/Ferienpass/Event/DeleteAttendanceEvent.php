<?php

namespace Ferienpass\Event;


use Ferienpass\Model\Attendance;
use Symfony\Component\EventDispatcher\Event;


class DeleteAttendanceEvent extends Event
{

    const NAME = 'ferienpass.model.attendance.delete';


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
