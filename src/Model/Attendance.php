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

use Contao\Database\Result;
use Contao\Model;
use Contao\System;
use Doctrine\DBAL\Connection;
use MetaModels\IFactory;
use MetaModels\IItem;
use Richardhj\ContaoFerienpassBundle\Helper\ToolboxOfferDate;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;


/**
 * Class Attendance
 *
 * @property integer $tstamp
 * @property integer $sorting
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
     * Fields used for "ORDER BY" sorting
     *
     * @var string
     */
    protected static $orderBy = 'offer,status,sorting';

    /**
     * @var IFactory
     */
    private $metaModelsFactory;

    /**
     * Attendance constructor.
     *
     * @param Result|null $result
     *
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    public function __construct(Result $result = null)
    {
        parent::__construct($result);

        $this->metaModelsFactory = System::getContainer()->get('metamodels.factory');
    }

    /**
     * @return AttendanceStatus|null
     */
    public function getStatus(): ?AttendanceStatus
    {
        try {
            /** @var AttendanceStatus $this ->getRelated('status') */
            return $this->getRelated('status');
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return IItem|null
     */
    public function getOffer(): ?IItem
    {
        $metaModel = $this->metaModelsFactory->getMetaModel('mm_ferienpass');

        return $metaModel->findById($this->offer);
    }

    /**
     * @return IItem|null
     */
    public function getParticipant(): ?IItem
    {
        $metaModel = $this->metaModelsFactory->getMetaModel('mm_participant');

        return $metaModel->findById($this->participant);
    }

    /**
     * Find attendances by participant's parent
     *
     * @param  integer $parentId
     *
     * @return Attendance|\Contao\Model\Collection|null
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    public static function findByParent($parentId)
    {
        //@todo this could be better with an associate db table

        /** @var Participant $participant */
        $participant = System::getContainer()->get('richardhj.ferienpass.model.participant');
        $filter      = $participant->byParentFilter($parentId);

        $participantIds = $filter->getMatchingIds();
        if (empty($participantIds)) {
            return null;
        }

        /** @var \Database\Result $result */
        $result = \Database::getInstance()->query(
            'SELECT * FROM ' . static::$strTable . ' WHERE participant IN(' . implode(
                ',',
                $participantIds
            ) . ') ORDER BY ' . static::getOrderBy()
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
            array_merge(
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
            array_merge(
                [
                    'order' => static::getOrderBy(),
                ],
                $options
            )
        );
    }

    /**
     * Find the last attendance with a given status made for an offer respecting the sorting
     *
     * @param int   $offerId
     * @param int   $statusId
     * @param array $options
     *
     * @return static
     */
    public static function findLastByOfferAndStatus(int $offerId, int $statusId, array $options = [])
    {
        return static::findOneBy(
            ['offer=?', 'status=?'],
            [$offerId, $statusId],
            array_merge(
                [
                    'order' => 'sorting DESC',
                ],
                $options
            )
        );
    }

    /**
     * Count participants in application list
     *
     * @param int $offerId
     *
     * @return int
     */
    public static function countParticipants($offerId): int
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
    public static function countByOffer($offerId): int
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
    public static function countByParticipant($participantId): int
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
    public static function countByOfferAndStatus($offerId, $statusId): int
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
    public static function countByParticipantAndDay($participantId, $timestamp = null, $status = 0): int
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
            $options['value'][]  = $status;
        }

        return static::countBy(null, null, $options);
    }

    /**
     * @param integer $participantId
     * @param integer $offerId
     *
     * @return bool
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    public static function isNotExistent($participantId, $offerId): bool
    {
        /** @var Connection $connection */
        $connection = System::getContainer()->get('database_connection');

        return 0 === $connection->createQueryBuilder()
                ->select('id')
                ->from(static::$strTable)
                ->where('participant=:participant')
                ->andWhere('offer=:offer')
                ->setParameter('participant', $participantId)
                ->setParameter('offer', $offerId)
                ->execute()
                ->rowCount();
    }

    /**
     * @return string
     */
    public static function getOrderBy(): string
    {
        return self::$orderBy;
    }

    /**
     * Update all attendance status for a given offer
     *
     * @param int $offerId
     */
    public static function updateStatusByOffer(int $offerId): void
    {
        // Do not update the attendances with the offer being in the past
        if (time() >= ToolboxOfferDate::offerStart($offerId)) {
            return;
        }

        $attendances = self::findByOffer($offerId);

        // Stop if the last attendance was deleted
        if (null === $attendances) {
            return;
        }

        while ($attendances->next()) {
            $attendances->save();
        }
    }
}
