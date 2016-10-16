<?php
/**
 * E-POSTBUSINESS API integration for Contao Open Source CMS
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package E-POST
 * @author  Richard Henkenjohann <richard-epost@henkenjohann.me>
 */

namespace Ferienpass\Event;


use Ferienpass\Model\Attendance;
use Symfony\Component\EventDispatcher\Event;


class ChangeAttendanceStatusEvent extends Event
{

    const NAME = 'ferienpass.model.attendance.change-status';


    /**
     * @var Attendance
     */
    protected $attendance;


    /**
     * ChangeAttendanceStatusEvent constructor.
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
