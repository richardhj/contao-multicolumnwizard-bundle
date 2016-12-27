<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Event;


use Ferienpass\Model\Attendance;
use Symfony\Component\EventDispatcher\Event;


/**
 * Class DeleteAttendanceEvent
 * @package Ferienpass\Event
 */
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
