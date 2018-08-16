<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\Model;

use Contao\Model;


/**
 * Class AttendanceReminder
 *
 * @property int $nc_notification
 * @property mixed $remind_before
 * @property int $attendance_status
 *
 * @package Richardhj\ContaoFerienpassBundle\Model
 */
class AttendanceReminder extends Model
{

    /**
     * The table name
     *
     * @var string
     */
    protected static $strTable = 'tl_ferienpass_attendance_reminder';

}
