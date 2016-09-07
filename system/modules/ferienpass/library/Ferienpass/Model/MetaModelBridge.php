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
use Ferienpass\Helper\Config as FerienpassConfig;
use MetaModels\Attribute\IAttribute;
use MetaModels\Factory;
use MetaModels\Filter\Filter;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IMetaModel;


abstract class MetaModelBridge
{

	/**
	 * Object instance (Singleton)
	 *
	 * @var MetaModelBridge
	 */
	private static $objInstance;


	/**
	 * The table name
	 *
	 * @var string
	 */
	protected $strTable;


	/**
	 * The MetaModel object
	 *
	 * @var IMetaModel
	 */
	protected $objMetaModel;


	/**
	 * The database object
	 *
	 * @var \Contao\Database
	 */
	protected $objDatabase;


	/**
	 * The MetaModel's owner attribute
	 *
	 * @type IAttribute
	 */
	protected $objOwnerAttribute;


	/**
	 * Load the MetaModel settings
	 */
	public function __construct()
	{
		$strConfigKey = lcfirst((new \ReflectionClass($this))->getShortName());

		// Get MetaModel object
		$objFactory = Factory::getDefaultFactory();
		$this->objMetaModel = $objFactory->getMetaModel(FerienpassConfig::get($strConfigKey . ':model'));

		// Exit if MetaModel object could not be created
		if (null === $this->objMetaModel)
		{
			return;
		}

		// Get table name
		$this->strTable = $this->objMetaModel->getTableName();

		// Get database object
		$this->objDatabase = $this->objMetaModel->getServiceContainer()->getDatabase();
	}


	/**
	 * Return the object instance (Singleton)
	 *
	 * @return static The object instance
	 */
	public static function getInstance()
	{
		if (static::$objInstance === null)
		{
			$strClass = get_called_class();
			static::$objInstance = new $strClass();
		}

		return static::$objInstance;
	}


	/**
	 * Return the MetaModel object
	 *
	 * @return \MetaModels\IMetaModel|null
	 */
	public function getMetaModel()
	{
		return $this->objMetaModel;
	}


	/**
	 * Return the owner attribute
	 *
	 * @return IAttribute
	 */
	public function getOwnerAttribute()
	{
		$this->fetchOwnerAttribute();

		return $this->objOwnerAttribute;
	}


	/**
	 * Find one item by its id
	 *
	 * @param $intId
	 *
	 * @return \MetaModels\IItem|null
	 */
	public function findById($intId)
	{
		return $this->objMetaModel->findById($intId);
	}


	/**
	 * Return all items as collection
	 *
	 * @return \MetaModels\IItem[]|\MetaModels\IItems
	 */
	public function findAll()
	{
		return $this->objMetaModel->findByFilter(null);
	}


	/**
	 * Return a set of items by given ids as collection
	 * 
	 * @param array $arrIds
	 *
	 * @return \MetaModels\IItem[]|\MetaModels\IItems
	 */
	public function findMultipleByIds($arrIds)
	{
		$objFilter = new Filter($this->objMetaModel);
		$objFilter->addFilterRule(new StaticIdList($arrIds));

		return $this->objMetaModel->findByFilter($objFilter);
	}


	/**
	 * Set MetaModel's owner attribute
	 */
	protected function fetchOwnerAttribute()
	{
		if ($this->objOwnerAttribute !== null)
		{
			return;
		}

		$this->objOwnerAttribute = $this->objMetaModel->getAttributeById($this->objMetaModel->get('owner_attribute'));

		if (null === $this->objOwnerAttribute)
		{
			throw new \RuntimeException('No owner attribute in the MetaModel was found');
		}
	}
}
