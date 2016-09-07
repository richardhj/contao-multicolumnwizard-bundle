<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Model;

use Contao\Model;


/**
 * Class AttendanceStatus
 * @property string  $name
 * @property string  $cssClass
 * @property string  $type
 * @property string  $title
 * @property integer $notification_new
 * @property integer $notification_onChange
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
	public static function findConfirmed()
	{
		return static::findByType('confirmed');
	}


	/**
	 * Find the status whose type is "waiting-list"
	 *
	 * @return AttendanceStatus
	 */
	public static function findWaiting()
	{
		return static::findByType('waiting');
	}


	/**
	 * Find the status whose type is "error"
	 *
	 * @return AttendanceStatus
	 */
	public static function findError()
	{
		return static::findByType('error');
	}


	/**
	 * Fine one message by its type
	 *
	 * @param string $strType
	 *
	 * @return AttendanceStatus
	 */
	public static function findByType($strType)
	{
		return static::findOneBy('type', $strType);
	}
}
