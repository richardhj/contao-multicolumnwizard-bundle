<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 * Copyright (c) 2015-2015 Richard Henkenjohann
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard-ferienpass@henkenjohann.me>
 */

namespace Ferienpass\Model;

use Contao\Model;
use Ferienpass\Helper\Config as FerienpassConfig;
use MetaModels\IItem;
use Model\Registry;
use NotificationCenter\Model\Notification;


/**
 * Class Attendance
 * @property integer $tstamp
 * @property integer $offer_id
 * @property integer $participant_id
 * @property integer $status
 *
 * @package Ferienpass
 */
class Attendance extends Model
{

	/**
	 * Table name
	 *
	 * @var string
	 */
	protected static $strTable = 'tl_ferienpass_attendance';


	/**
	 * The participant model
	 *
	 * @var Participant
	 */
	protected static $objParticipant;


	/**
	 * Find attendances by offer
	 *
	 * @param integer $intOfferId
	 * @param array   $arrOptions
	 *
	 * @return \Model\Collection|null
	 */
	public static function findByOffer($intOfferId, array $arrOptions = array())
	{
		return static::findBy(
			'offer_id',
			$intOfferId,
			array_merge
			(
				array
				(
					'order' => 'tstamp,id'
				),
				$arrOptions
			)
		);
	}


	/**
	 * Find attendances by participant's parent
	 *
	 * @param  integer $intParentId
	 *
	 * @return Attendance|\Model\Collection|null
	 */
	public static function findByParent($intParentId)
	{
		//@todo this could be better with an associate db table

		/** @var \MetaModels\Filter\Filter $objFilter */
		$objFilter = Participant::getInstance()->byParentFilter($intParentId);

		$arrParticipantIds = $objFilter->getMatchingIds();

		if (empty($arrParticipantIds))
		{
			return null;
		}

		/** @var \Database\Result $objResult */
		$objResult = \Database::getInstance()->query("SELECT * FROM " . static::$strTable . " WHERE participant_id IN(" . implode(',', $arrParticipantIds) . ") ORDER BY tstamp,id");

		return static::createCollectionFromDbResult($objResult, static::$strTable);
	}


	/**
	 * Find attendance by its position
	 *
	 * @param integer $intPosition
	 * @param integer $intOfferId
	 *
	 * @return Attendance|\Model\Collection|null
	 */
	public static function findByPosition($intPosition, $intOfferId)
	{
		return static::findByOffer($intOfferId, array
		(
			'limit'  => 1,
			'offset' => $intPosition - 1
		));
	}


	/**
	 * Count participants in application list
	 *
	 * @param int $intOfferId
	 *
	 * @return int
	 */
	public static function countParticipants($intOfferId)
	{
		return static::countByOffer($intOfferId);
	}


	/**
	 * Count participants for one particular offer
	 *
	 * @param int $intOfferId
	 *
	 * @return int
	 */
	public static function countByOffer($intOfferId)
	{
		return static::countBy('offer_id', $intOfferId);
	}


	/**
	 * Count attendances for one particular participant
	 *
	 * @param int $intParticipantId
	 *
	 * @return int
	 */
	public static function countByParticipant($intParticipantId)
	{
		return static::countBy('participant_id', $intParticipantId);
	}


	/**
	 * Count the attendances of a participant made on a particular day and optionally with a particular status
	 *
	 * @param integer $intParticipantId
	 * @param integer $intTimestamp
	 * @param integer $intStatus
	 *
	 * @return int
	 */
	public static function countByParticipantAndDay($intParticipantId, $intTimestamp = null, $intStatus = 0)
	{
		if (null === $intTimestamp)
		{
			$intTimestamp = time();
		}

		$objDate = new \Date($intTimestamp);

		$arrOptions = array
		(
			'column' => array('participant_id=?', 'tstamp>=?', 'tstamp<=?'),
			'value'  => array($intParticipantId, $objDate->dayBegin, $objDate->dayEnd)
		);

		if ($intStatus)
		{
			$arrOptions['column'][] = 'status=?';
			$arrOptions['value'][] = $intStatus;
		}

		return static::countBy(null, null, $arrOptions);
	}


	/**
	 * @param integer $intParticipantId
	 * @param integer $intOfferId
	 *
	 * @return bool
	 */
	public static function isNotExistent($intParticipantId, $intOfferId)
	{
		return !(\Database::getInstance()
			->prepare("SELECT id FROM " . static::$strTable . " WHERE participant_id=? AND offer_id=?")
			->execute($intParticipantId, $intOfferId)
			->numRows);
	}


	/**
	 * Trigger notification if attendance is new created
	 * {@inheritdoc}
	 */
	public function save()
	{
		$blnNewAttendance = (!Registry::getInstance()->isRegistered($this));

		// Save model
		parent::save();

		if ($blnNewAttendance)
		{
			// Trigger notification
			/**
			 * @var AttendanceStatus $this ->getStatus()
			 * @var Notification     $objNotification
			 */
			/** @noinspection PhpUndefinedMethodInspection */
			$objNotification = Notification::findByPk($this->getStatus()->notification_new);

			$objParticipant = Participant::getInstance()->findById($this->participant_id);
			$objOffer = Offer::getInstance()->findById($this->offer_id);

			// Send the notification if one is set
			if (null !== $objNotification)
			{
				$objNotification->send(static::getNotificationTokens($objParticipant, $objOffer));
			}
		}
	}


	/**
	 * Delete attendance and trigger status update actions
	 *
	 * @return void
	 */
	public function delete()
	{
		$intOfferId = $this->offer_id;

		if (parent::delete())
		{
			$this->updateStatusByOffer($intOfferId);
		}
	}


	/**
	 * Get attendance's current position
	 * @return integer|null if participant not in attendance list (yet) or has error status
	 */
	public function getPosition()
	{
		$objAttendances = static::findByOffer($this->offer_id); # collection ordered by tstamp,id

		if (null === $objAttendances)
		{
			return null;
		}

		for ($i = 1; $objAttendances->next(); $i++)
		{
			// Attendances with error do not increase the index
			/** @var Attendance $objAttendances ->current() */
			if ($objAttendances->current()->status == AttendanceStatus::findError()->id)
			{
				--$i;
				continue;
			}

			if ($objAttendances->current()->participant_id == $this->participant_id)
			{
				return $i;
			}
		}

		return null;
	}


	/**
	 * Get attendance's status object
	 *
	 * @return AttendanceStatus
	 */
	public function getStatus()
	{
		/** @type IItem $objOffer */
		$objOffer = Offer::getInstance()->findById($this->offer_id);

		// An error status is persistent
		if ($this->status == AttendanceStatus::findError()->id)
		{
			return AttendanceStatus::findError();
		}

		// Offers without usage of application list or without limit
		if (!$objOffer->get(FerienpassConfig::get(FerienpassConfig::OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE))
			|| !($max = $objOffer->get(FerienpassConfig::get(FerienpassConfig::OFFER_ATTRIBUTE_APPLICATIONLIST_MAX)))
		)
		{
			return AttendanceStatus::findConfirmed();
		}

		$position = $this->getPosition();

		if (null !== $position)
		{
			if ($position <= $max)
			{
				return AttendanceStatus::findConfirmed();
			}
			else
			{
				return AttendanceStatus::findWaiting();
			}
		}
		// Attendance not saved yet
		else
		{
			if (static::countParticipants($objOffer->get('id')) < $max) # use '<' here because the count will be increased after saving the new attendance
			{
				return AttendanceStatus::findConfirmed();
			}
			else
			{
				return AttendanceStatus::findWaiting();
			}
		}
	}


	/**
	 * Update all attendance statuses for one offer
	 *
	 * @param integer $intOfferId
	 */
	public static function updateStatusByOffer($intOfferId)
	{
		$objAttendances = static::findByOffer($intOfferId);

		// Stop if the last attendance was deleted
		if (null === $objAttendances)
		{
			return;
		}

		while ($objAttendances->next())
		{
			/**
			 * @var Attendance       $objAttendances ->current()
			 * @var AttendanceStatus $objStatus
			 */
			$objStatus = $objAttendances->current()->getStatus();

			if ($objAttendances->status != $objStatus->id)
			{
				static::processStatusChange($objAttendances->current(), $objStatus);
			}
		}
	}


	/**
	 * Process status change and trigger notifications for one attendance
	 *
	 * @param Attendance|Model $objAttendance
	 * @param AttendanceStatus $objNewStatus
	 */
	protected static function processStatusChange($objAttendance, $objNewStatus)
	{
		$objParticipant = Participant::getInstance()->findById($objAttendance->participant_id);
		$objOffer = Offer::getInstance()->findById($objAttendance->offer_id);

		/** @var AttendanceStatus $objOldStatus */
		$objOldStatus = $objAttendance->getRelated('status');

		// Set status
		$objAttendance->status = $objNewStatus->id;

		// Attendances are not up to date because participant or offer might be deleted
		if (null === $objOffer || null === $objParticipant)
		{
			// Set status
			$objAttendance->status = AttendanceStatus::findError()->id;

			\System::log(sprintf(
				'Status "%s" was added to attendance ID %u as the %s is not existent',
				AttendanceStatus::findError()->type,
				$objAttendance->id,
				(null === $objOffer) ? 'offer' : 'participant'
			), __METHOD__, TL_ERROR);
		}

		// Save attendance
		$objAttendance->save();

		/** @var AttendanceStatus $objNewStatus */
		$objNewStatus = $objAttendance->getRelated('status');

		\System::log(sprintf(
			'Status for attendance ID %u and participant ID %u was changed from "%s" to "%s"',
			$objAttendance->id,
			$objParticipant->get('id'),
			$objOldStatus->type,
			$objNewStatus->type
		), __METHOD__, TL_GENERAL);

		/** @var Notification $objNotification */
		/** @noinspection PhpUndefinedMethodInspection */
		$objNotification = Notification::findByPk($objNewStatus->notification_onChange);

		// Send the notification if one is set
		if (null !== $objNotification)
		{
			$objNotification->send(static::getNotificationTokens($objParticipant, $objOffer));
//			// Log sent mails
//			foreach ($objNotification->send(static::getNotificationTokens($objParticipant, $objOffer)) as $message => $success)
//			{
//				\System::log(sprintf(
//					'Message ID %u for participant ID %u and offer ID %u %s sent.',
//					$message,
//					$objParticipant->get('id'),
//					$objOffer->get('id'),
//					!$success ? 'failed to' : 'was'
//				), __METHOD__, TL_GENERAL);
//			}
		}
	}


	/**
	 * Get notification tokens
	 *
	 * @param IItem $objParticipant
	 * @param IItem $objOffer
	 *
	 * @return array
	 */
	public static function getNotificationTokens($objParticipant, $objOffer)
	{
		$arrTokens = array();

		// Add all offer fields
		foreach ($objOffer->getMetaModel()->getAttributes() as $name => $attribute)
		{
			$arrTokens['offer_' . $name] = $objOffer->get($name);
		}

		// Add all the participant fields
		foreach ($objParticipant->getMetaModel()->getAttributes() as $name => $attribute)
		{
			$arrTokens['participant_' . $name] = $objParticipant->get($name);
		}

		// Add all the parent's member fields
		$objOwnerAttribute = $objParticipant->getMetaModel()->getAttributeById($objParticipant->getMetaModel()->get('owner_attribute'));
		foreach ($objParticipant->get($objOwnerAttribute->getColName()) as $k => $v)
		{
			$arrTokens['member_' . $k] = $v;
		}

		// Add the participant's email
		$arrTokens['participant_email'] = $arrTokens['participant_email'] ?: $arrTokens['member_email'];

		// Add the host's email
		$arrTokens['host_email'] = $objOffer->get($objOffer->getMetaModel()->get('owner_attribute'))['email'];

		// Add the admin's email
		$arrTokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];

		return $arrTokens;
	}
}
