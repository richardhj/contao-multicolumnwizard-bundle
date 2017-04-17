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
use MetaModels\IItem;


/**
 * Class Attendance
 * @property integer $tstamp
 * @property integer $created
 * @property integer $offer
 * @property integer $participant
 * @property integer $status
 *
 * @package Ferienpass
 */
class Attendance extends Model
{

    use Model\DispatchModelEventsTrait;

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


    protected static $orderBy = 'offer,status,sorting';


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

        if (empty($participantIds)) {
            return null;
        }

        /** @var \Database\Result $result */
        $result = \Database::getInstance()->query(
            "SELECT * FROM ".static::$strTable." WHERE participant IN(".implode(
                ',',
                $participantIds
            ).") ORDER BY ".static::getOrderBy()
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
        return static::findByOffer(
            $offerId,
            [
                'limit'  => 1,
                'offset' => $position - 1,
            ]
        );
    }


    /**
     * Find attendances by offer
     *
     * @param integer $offerId
     * @param array   $options
     *
     * @return Attendance|\Model\Collection|null
     */
    public static function findByOffer($offerId, array $options = [])
    {
        return static::findBy(
            'offer',
            $offerId,
            array_merge
            (
                [
                    'order' => static::getOrderBy(),
                ],
                $options
            )
        );
    }


    /**
     * Find attendances by offer
     *
     * @param integer $participantId
     * @param array   $options
     *
     * @return Attendance|\Model\Collection|null
     */
    public static function findByParticipant($participantId, array $options = [])
    {
        return static::findBy(
            'participant',
            $participantId,
            array_merge
            (
                [
                    'order' => static::getOrderBy(),
                ],
                $options
            )
        );
    }


    public static function findLastByOfferAndStatus($offerId, $statusId, array $options = [])
    {
        return static::findOneBy(
            ['offer=?', 'status=?'],
            [$offerId, $statusId],
            array_merge
            (
                [
                    'order' => 'sorting DESC',
                ],
                $options
            )
        );
    }


    public static function findNotSent(array $options = [])
    {
        return static::findBy(
            ['id NOT IN (SELECT attendance FROM tl_ferienpass_attendance_notification WHERE tstamp<>0)'],
            [],
            $options
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
     * Count the attendances of a offer with a particular status
     *
     * @param integer $offerId
     * @param integer $statusId
     *
     * @return int
     */
    public static function countByOfferAndStatus($offerId, $statusId)
    {
        return static::countBy(['offer=?', 'status=?'], [$offerId, $statusId]);
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
        if (null === $timestamp) {
            $timestamp = time();
        }

        $date = new \Date($timestamp);

        $options = [
            'column' => ['participant=?', 'tstamp>=?', 'tstamp<=?'],
            'value'  => [$participantId, $date->dayBegin, $date->dayEnd],
        ];

        if (0 !== $status) {
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
     * @return string
     */
    public static function getOrderBy()
    {
        return self::$orderBy;
    }


    /**
     * Get attendance's current position
     * @return integer|null if participant not in attendance list (yet) or has error status
     */
    public function getPosition()
    {
        /** @var Attendance|\Model\Collection $attendances */
        $attendances = static::findByOffer($this->offer); // Collection is already ordered

        if (null === $attendances) {
            return null;
        }

        for ($i = 0; $attendances->next(); $i++) {
            if (!$attendances->current()->getStatus()->increasesCount) {
                --$i;
                continue;
            }

            if ($attendances->current()->participant == $this->participant) {
                return $i;
            }
        }

        return null;
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
}
