<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\Model;

use MetaModels\Filter\IFilter;
use MetaModels\Filter\IFilterRule;
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IItem;
use MetaModels\IItems;


/**
 * Class Participant
 *
 * @package Richardhj\ContaoFerienpassBundle\Model
 */
class Participant extends AbstractSimpleMetaModel
{

    /**
     * Participant constructor.
     */
    public function __construct()
    {
        parent::__construct('mm_participant');
    }

    /**
     * Find multiple participants by its parent (member) id
     *
     * @param integer $parentId
     *
     * @return \MetaModels\IItem[]|\MetaModels\IItems
     */
    public function findByParent(int $parentId): IItems
    {
        return $this->metaModel->findByFilter($this->byParentFilter($parentId));
    }

    /**
     * Return the filter
     *
     * @param integer $parentId
     *
     * @return IFilter
     */
    public function byParentFilter(int $parentId): IFilter
    {
        $filter = $this->metaModel->getEmptyFilter();
        $filter->addFilterRule($this->byParentFilterRule($parentId));

        return $filter;
    }

    /**
     * Return the filter rule
     *
     * @param integer $parentId
     *
     * @return IFilterRule
     * @throws \LogicException
     */
    protected function byParentFilterRule(int $parentId): IFilterRule
    {
        return new SimpleQuery(
            sprintf(
                'SELECT id FROM %1$s WHERE %2$s=?',
                $this->getMetaModel()->getTableName(),
                'pmember'
            ),
            [$parentId]
        );
    }

    /**
     * Find multiple participants by its parent and offer id
     *
     * @param integer $parentId
     * @param integer $offerId
     *
     * @return \MetaModels\IItem[]|\MetaModels\IItems
     */
    public function findByParentAndOffer(int $parentId, int $offerId): IItems
    {
        return $this->metaModel->findByFilter($this->byParentAndOfferFilter($parentId, $offerId));
    }

    /**
     * Return the filter
     *
     * @param integer $parentId
     * @param integer $offerId
     *
     * @return IFilter
     */
    public function byParentAndOfferFilter(int $parentId, int $offerId): IFilter
    {
        $filter = $this->metaModel->getEmptyFilter();
        $filter->addFilterRule($this->byParentFilterRule($parentId));
        $filter->addFilterRule($this->byOfferFilterRule($offerId));

        return $filter;
    }

    /**
     * Return the filter rule
     *
     * @param integer $offerId
     *
     * @return IFilterRule
     */
    protected function byOfferFilterRule(int $offerId): IFilterRule
    {
        $attendances = Attendance::findByOffer($offerId);
        if (null === $attendances) {
            return new StaticIdList([]);
        }

        return new StaticIdList(array_values($attendances->fetchEach('participant')));
    }

    /**
     * Check whether the participant is a member's child
     *
     * @param integer $childId
     * @param integer $parentId
     *
     * @return bool
     */
    public function isProperChild(int $childId, int $parentId): bool
    {
        /** @var IItem $child */
        $child = $this->metaModel->findById($childId);
        if (null === $child) {
            return false;
        }

        return $parentId == $child->get('pmember')['id'];
    }
}
