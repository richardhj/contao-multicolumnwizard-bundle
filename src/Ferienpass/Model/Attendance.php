<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 * Copyright (c) 2015-2015 Richard Henkenjohann
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard-ferienpass@henkenjohann.me>
 */

namespace Ferienpass\Model;

use Contao\Model;
use Ferienpass\ApplicationSystem\ApplicationSystemInterface;
use Ferienpass\Event\ChangeAttendanceStatusEvent;
use Ferienpass\Event\SaveAttendanceEvent;
use MetaModels\IItem;
use Model\Registry;
use Symfony\Component\EventDispatcher\EventDispatcher;


/**
 * Class Attendance
 * @property integer $tstamp
 * @property integer $offer
 * @property integer $participant
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
	protected static $participant;


	/**
	 * Find attendances by offer
	 *
	 * @param integer $offerId
	 * @param array   $options
	 *
	 * @return \Model\Collection|null
	 */
	public static function findByOffer($offerId, array $options = [])
	{
		return static::findBy(
            'offer',
			$offerId,
			array_merge
			(
				[
					'order' => 'tstamp,id'
                ],
				$options
			)
		);
	}


	/**
	 * Find attendances by participant's parent
	 *
	 * @param  integer $parentId
	 *
	 * @return Attendance|\Model\Collection|null
	 */
	public static function findByParent($parentId)
	{
		//@todo this could be better with an associate db table

		/** @var \MetaModels\Filter\Filter $filter */
		$filter = Participant::getInstance()->byParentFilter($parentId);

		$participantIds = $filter->getMatchingIds();

		if (empty($participantIds))
		{
			return null;
		}

		/** @var \Database\Result $result */
        $result = \Database::getInstance()->query(
            "SELECT * FROM ".static::$strTable." WHERE participant IN(".implode(
                ',',
                $participantIds
            ).") ORDER BY tstamp,id"
        );

		return static::createCollectionFromDbResult($result, static::$strTable);
	}


	/**
	 * Find attendance by its position
	 *
	 * @param integer $position
	 * @param integer $offerId
	 *
	 * @return Attendance|\Model\Collection|null
	 */
	public static function findByPosition($position, $offerId)
	{
		return static::findByOffer($offerId,            [
			'limit'  => 1,
			'offset' => $position - 1
            ]
        );
	}


	/**
	 * Count participants in application list
	 *
	 * @param int $offerId
	 *
	 * @return int
	 */
	public static function countParticipants($offerId)
	{
		return static::countByOffer($offerId);
	}


	/**
	 * Count participants for one particular offer
	 *
	 * @param int $offerId
	 *
	 * @return int
	 */
	public static function countByOffer($offerId)
	{
        return static::countBy('offer', $offerId);
	}


	/**
	 * Count attendances for one particular participant
	 *
	 * @param int $participantId
	 *
	 * @return int
	 */
	public static function countByParticipant($participantId)
	{
        return static::countBy('participant', $participantId);
	}


	/**
	 * Count the attendances of a participant made on a particular day and optionally with a particular status
	 *
	 * @param integer $participantId
	 * @param integer $timestamp
	 * @param integer $status
	 *
	 * @return int
	 */
	public static function countByParticipantAndDay($participantId, $timestamp = null, $status = 0)
	{
		if (null === $timestamp)
		{
			$timestamp = time();
		}

		$date = new \Date($timestamp);

		$options =            [
            'column' => ['participant=?', 'tstamp>=?', 'tstamp<=?'],
            'value'  => [$participantId, $date->dayBegin, $date->dayEnd]
            ];

		if ($status)
		{
			$options['column'][] = 'status=?';
			$options['value'][] = $status;
		}

		return static::countBy(null, null, $options);
	}


	/**
	 * @param integer $participantId
	 * @param integer $offerId
	 *
	 * @return bool
	 */
	public static function isNotExistent($participantId, $offerId)
	{
		return !(\Database::getInstance()
            ->prepare("SELECT id FROM ".static::$strTable." WHERE participant=? AND offer=?")
			->execute($participantId, $offerId)
			->numRows);
	}


	/**
	 * Trigger notification if attendance is new created
	 * {@inheritdoc}
	 */
	public function save()
	{
        global $container;
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $container['event-dispatcher'];

        $isRegistered = (!Registry::getInstance()->isRegistered($this));

		// Save model
		parent::save();

        $event = new SaveAttendanceEvent($this, $isRegistered);
        $dispatcher->dispatch(SaveAttendanceEvent::NAME, $event);
	}


	/**
	 * Delete attendance and trigger status update actions
	 *
	 * @return void
	 */
	public function delete()
	{
        $offer = $this->offer;

		if (parent::delete())
		{
			$this->updateStatusByOffer($offer);
		}
	}


	/**
	 * Get attendance's current position
     * @return integer|null if participant not in attendance list (yet) or has error status. Index starting from 1
	 */
	public function getPosition()
	{
        $attendances = static::findByOffer($this->offer); # collection ordered by tstamp,id

		if (null === $attendances)
		{
			return null;
		}

		for ($i = 1; $attendances->next(); $i++)
		{
            if (!$attendances->current()->getRelated('status')->increasesCount)
			{
				--$i;
				continue;
			}

            if ($attendances->current()->participant == $this->participant)
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
    public function fetchStatus()
	{
        global $container;

        /** @var ApplicationSystemInterface $applicationSystem */
        $applicationSystem = $container['ferienpass.applicationsystem'];

        return $applicationSystem->findAttendanceStatus($this, Offer::getInstance()->findById($this->offer));
    }


    /**
     * @return IItem
     */
    public function getOffer()
    {
        return Offer::getInstance()->findById($this->offer);
    }


    /**
     * @return IItem|null
     */
    public function getParticipant()
    {
        return Participant::getInstance()->findById($this->participant);
    }


    /**
     * @return AttendanceStatus
     */
    public function getStatus()
    {
        /** @var AttendanceStatus $this ->getRelated('status') */
        return $this->getRelated('status');
	}


	/**
	 * Update all attendance statuses for one offer
	 *
	 * @param integer $offerId
	 */
	public static function updateStatusByOffer($offerId)
	{
		$attendances = static::findByOffer($offerId);

		// Stop if the last attendance was deleted
		if (null === $attendances)
		{
			return;
		}

		while ($attendances->next())
		{
			/**
			 * @var Attendance       $attendances ->current()
			 * @var AttendanceStatus $status
			 */
            $status = $attendances->current()->fetchStatus();

			if ($attendances->status != $status->id)
			{
				static::processStatusChange($attendances->current(), $status);
			}
		}
	}


	/**
	 * Process status change and trigger notifications for one attendance
	 *
	 * @param Attendance|Model $attendance
	 * @param AttendanceStatus $newStatus
	 */
	protected static function processStatusChange($attendance, $newStatus)
	{
        global $container;
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $container['event-dispatcher'];

        $participant = Participant::getInstance()->findById($attendance->participant);
        $offer = Offer::getInstance()->findById($attendance->offer);

		/** @var AttendanceStatus $oldStatus */
		$oldStatus = $attendance->getRelated('status');

		// Set status
		$attendance->status = $newStatus->id;

		// Attendances are not up to date because participant or offer might be deleted
		if (null === $offer || null === $participant)
		{
			// Set status
			$attendance->status = AttendanceStatus::findError()->id;

			\System::log(sprintf(
				'Status "%s" was added to attendance ID %u as the %s is not existent',
				AttendanceStatus::findError()->type,
				$attendance->id,
				(null === $offer) ? 'offer' : 'participant'
			), __METHOD__, TL_ERROR);
		}

		// Save attendance
		$attendance->save();

        $event = new ChangeAttendanceStatusEvent($attendance);
        $dispatcher->dispatch(ChangeAttendanceStatusEvent::NAME, $event);

		\System::log(sprintf(
			'Status for attendance ID %u and participant ID %u was changed from "%s" to "%s"',
			$attendance->id,
			$participant->get('id'),
			$oldStatus->type,
            $attendance->getStatus()->type
		), __METHOD__, TL_GENERAL);
	}


	/**
	 * Get notification tokens
	 *
	 * @param IItem $participant
	 * @param IItem $offer
	 *
	 * @return array
	 */
	public static function getNotificationTokens($participant, $offer)
	{
		$tokens = [];

		// Add all offer fields
		foreach ($offer->getMetaModel()->getAttributes() as $name => $attribute)
		{
			$tokens['offer_' . $name] = $offer->get($name);
		}

		// Add all the participant fields
		foreach ($participant->getMetaModel()->getAttributes() as $name => $attribute)
		{
			$tokens['participant_' . $name] = $participant->get($name);
		}

		// Add all the parent's member fields
		$objOwnerAttribute = $participant->getMetaModel()->getAttributeById($participant->getMetaModel()->get('owner_attribute'));
		foreach ($participant->get($objOwnerAttribute->getColName()) as $k => $v)
		{
			$tokens['member_' . $k] = $v;
		}

		// Add the participant's email
		$tokens['participant_email'] = $tokens['participant_email'] ?: $tokens['member_email'];

		// Add the host's email
		$tokens['host_email'] = $offer->get($offer->getMetaModel()->get('owner_attribute'))['email'];

		// Add the admin's email
		$tokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];

		return $tokens;
	}
}
