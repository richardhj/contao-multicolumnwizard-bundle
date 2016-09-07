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

use Contao\Database;
use Contao\Model;
use MetaModels\Filter\Filter;
use MetaModels\Filter\IFilter;
use MetaModels\Filter\IFilterRule;
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\Item;


class Participant extends MetaModelBridge
{

	/**
	 * The object instance
	 *
	 * @var Participant
	 */
	protected static $objInstance;
	

	/**
	 * Find multiple participants by its parent (member) id
	 *
	 * @param integer $intParentId
	 *
	 * @return \MetaModels\IItem[]|\MetaModels\IItems
	 */
	public function findByParent($intParentId)
	{
		return $this->objMetaModel->findByFilter($this->byParentFilter($intParentId));
	}


	/**
	 * Find multiple participants by its parent and offer id
	 *
	 * @param integer $intParentId
	 * @param integer $intOfferId
	 *
	 * @return \MetaModels\IItem[]|\MetaModels\IItems
	 */
	public function findByParentAndOffer($intParentId, $intOfferId)
	{
		return $this->objMetaModel->findByFilter($this->byParentAndOfferFilter($intParentId, $intOfferId));
	}


	/**
	 * Return the filter
	 *
	 * @param integer $intParentId
	 *
	 * @return IFilter
	 */
	public function byParentFilter($intParentId)
	{
		$objFilter = new Filter($this->objMetaModel);
		$objFilter->addFilterRule($this->byParentFilterRule($intParentId));

		return $objFilter;
	}


	/**
	 * Return the filter
	 *
	 * @param integer $intParentId
	 * @param integer $intOfferId
	 *
	 * @return IFilter
	 */
	public function byParentAndOfferFilter($intParentId, $intOfferId)
	{
		$objFilter = new Filter($this->objMetaModel);
		$objFilter->addFilterRule($this->byParentFilterRule($intParentId));
		$objFilter->addFilterRule($this->byOfferFilterRule($intOfferId));

		return $objFilter;
	}


	/**
	 * Return the filter rule
	 *
	 * @param integer $intParentId
	 *
	 * @return IFilterRule
	 * @throws \LogicException
	 */
	protected function byParentFilterRule($intParentId)
	{
		$objOwnerAttribute = $this->objMetaModel->getAttributeById($this->objMetaModel->get('owner_attribute'));

		if (null === $objOwnerAttribute)
		{
			throw new \LogicException(sprintf('No owner attribute for MetaModel ID %u defined', $this->objMetaModel->get('id')));
		}

		return new SimpleQuery(sprintf
		(
			'SELECT id FROM %1$s WHERE %2$s=?',
			$this->strTable,
			$objOwnerAttribute->getColName()
		),
			array($intParentId)
		);
	}


	/**
	 * Return the filter rule
	 *
	 * @param integer $intOfferId
	 *
	 * @return IFilterRule
	 */
	protected function byOfferFilterRule($intOfferId)
	{
		$objAttendances = Attendance::findByOffer($intOfferId);

		if (null !== $objAttendances)
		{
			return new StaticIdList(array_values($objAttendances->fetchEach('participant_id')));
		}

		return new StaticIdList(array());
	}


	/**
	 * Check whether the participant is a member's child
	 *
	 * @param integer $intChildId
	 * @param integer $intParentId
	 *
	 * @return bool
	 * @throws \LogicException
	 */
	public function isProperChild($intChildId, $intParentId)
	{
		/** @var Item|null $objChild */
		$objChild = $this->objMetaModel->findById($intChildId);
		$objOwnerAttribute = $this->objMetaModel->getAttributeById($this->objMetaModel->get('owner_attribute'));

		if (null === $objChild)
		{
			return false;
		}
		if (null === $objOwnerAttribute)
		{
			throw new \LogicException(sprintf('No owner attribute for MetaModel ID %u defined', $this->objMetaModel->get('id')));
		}

		return ($objChild->get($objOwnerAttribute->getColName())['id'] == $intParentId);
	}
}
