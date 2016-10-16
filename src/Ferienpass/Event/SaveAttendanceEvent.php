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
     * @var bool
     */
    protected $newModel;


    /**
     * SaveAttendanceEvent constructor.
     *
     * @param Attendance $attendance
     * @param bool       $newModel
     */
    public function __construct(Attendance $attendance, $newModel)
    {
        $this->attendance = $attendance;
        $this->newModel = $newModel;
    }


    /**
     * @return Attendance
     */
    public function getAttendance()
    {
        return $this->attendance;
    }


    /**
     * @return boolean
     */
    public function isNewModel()
    {
        return $this->newModel;
    }
}
