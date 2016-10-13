<?php
/**
 * Created by PhpStorm.
 * User: richard
 * Date: 13.10.16
 * Time: 19:18
 */

namespace Ferienpass\ApplicationSystem;


use Ferienpass\Model\Attendance;
use Ferienpass\Model\AttendanceStatus;
use MetaModels\IItem;


interface ApplicationSystemInterface
{

    /**
     * @param Attendance $attendance
     * @param IItem      $offer
     *
     * @return AttendanceStatus
     */
    public function findAttendanceStatus(Attendance $attendance, IItem $offer);
}
