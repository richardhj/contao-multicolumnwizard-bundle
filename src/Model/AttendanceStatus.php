<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\Model;

use Contao\Model;


/**
 * Class AttendanceStatus
 *
 * @property string  $name
 * @property string  $type
 * @property string  $title
 * @property integer $notification_new
 * @property integer $notification_onChange
 * @property string  $messageType
 *
 * @package Ferienpass
 */
class AttendanceStatus extends Model
{

    /**
     * The table name
     *
     * @var string
     */
    protected static $strTable = 'tl_ferienpass_attendancestatus';

    /**
     * Find the status whose type is "confirmed"
     *
     * @return AttendanceStatus
     */
    public static function findConfirmed(): AttendanceStatus
    {
        return static::findByType('confirmed');
    }

    /**
     * Find the status whose type is "waiting"
     *
     * @return AttendanceStatus
     */
    public static function findWaiting(): AttendanceStatus
    {
        return static::findByType('waiting');
    }

    /**
     * Find the status whose type is "on waiting-list"
     *
     * @return AttendanceStatus
     */
    public static function findWaitlisted(): AttendanceStatus
    {
        return static::findByType('waitlisted');
    }

    /**
     * Find the status whose type is "error"
     *
     * @return AttendanceStatus
     */
    public static function findError(): AttendanceStatus
    {
        return static::findByType('error');
    }

    /**
     * Fine one message by its type
     *
     * @param string $type
     *
     * @return AttendanceStatus
     */
    public static function findByType($type): AttendanceStatus
    {
        $model = static::findOneBy('type', $type);
        if (null === $model) {
            throw new \OutOfBoundsException('Attendance status not existent: ' . $type);
        }

        return $model;
    }
}
