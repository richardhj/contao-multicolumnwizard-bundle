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
use Ferienpass\Model\AttendanceStatus;
use Symfony\Component\EventDispatcher\Event;


class ChangeAttendanceStatusEvent extends Event
{

    const NAME = 'ferienpass.model.attendance.change-status';


    /**
     * @var Attendance
     */
    protected $attendance;


    /**
     * @var AttendanceStatus
     */
    protected $oldStatus;


    /**
     * @var AttendanceStatus
     */
    protected $newStatus;


    /**
     * ChangeAttendanceStatusEvent constructor.
     *
     * @param Attendance            $attendance
     * @param AttendanceStatus|null $oldStatus
     * @param AttendanceStatus      $newStatus
     */
    public function __construct(Attendance $attendance, $oldStatus, AttendanceStatus $newStatus)
    {

        $this->attendance = $attendance;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }


    /**
     * @return Attendance
     */
    public function getAttendance()
    {
        return $this->attendance;
    }


    /**
     * @return AttendanceStatus
     */
    public function getOldStatus()
    {
        return $this->oldStatus;
    }


    /**
     * @return AttendanceStatus
     */
    public function getNewStatus()
    {
        return $this->newStatus;
    }
}
