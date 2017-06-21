<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Model;

use Contao\Model;


/**
 * Class AttendanceReminder
 *
 * @property int $nc_notification
 * @property mixed $remind_before
 *
 * @package Ferienpass\Model
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
