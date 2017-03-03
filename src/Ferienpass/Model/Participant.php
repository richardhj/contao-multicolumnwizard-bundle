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

use MetaModels\Filter\IFilter;
use MetaModels\Filter\IFilterRule;
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\Item;


/**
 * Class Participant
 * @package Ferienpass\Model
 */
class Participant extends MetaModelBridge
{

    /**
     * The object instance
     *
     * @var Participant
     */
    protected static $instance;


    /**
     * The table name
     *
     * @var string
     */
    protected static $tableName = 'mm_participant';


    /**
     * Find multiple participants by its parent (member) id
     *
     * @param integer $parentId
     *
     * @return \MetaModels\IItem[]|\MetaModels\IItems
     */
    public function findByParent($parentId)
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
    public function byParentFilter($parentId)
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
    protected function byParentFilterRule($parentId)
    {
        $ownerAttribute = $this->metaModel->getAttributeById($this->metaModel->get('owner_attribute'));

        if (null === $ownerAttribute) {
            throw new \LogicException(
                sprintf('No owner attribute for MetaModel ID %u defined', $this->metaModel->get('id'))
            );
        }

        return new SimpleQuery(
            sprintf(
                'SELECT id FROM %1$s WHERE %2$s=?',
                $this->getTableName(),
                $ownerAttribute->getColName()
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
    public function findByParentAndOffer($parentId, $offerId)
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
    public function byParentAndOfferFilter($parentId, $offerId)
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
    protected function byOfferFilterRule($offerId)
    {
        $attendances = Attendance::findByOffer($offerId);

        if (null !== $attendances) {
            return new StaticIdList(array_values($attendances->fetchEach('participant')));
        }

        return new StaticIdList([]);
    }


    /**
     * Check whether the participant is a member's child
     *
     * @param integer $childId
     * @param integer $parentId
     *
     * @return bool
     * @throws \LogicException
     */
    public function isProperChild($childId, $parentId)
    {
        /** @var Item|null $child */
        $child = $this->metaModel->findById($childId);
        $ownerAttribute = $this->metaModel->getAttributeById($this->metaModel->get('owner_attribute'));

        if (null === $child) {
            return false;
        }
        if (null === $ownerAttribute) {
            throw new \LogicException(
                sprintf('No owner attribute for MetaModel ID %u defined', $this->metaModel->get('id'))
            );
        }

        return ($child->get($ownerAttribute->getColName())['id'] == $parentId);
    }
}
