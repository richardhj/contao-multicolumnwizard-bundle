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
    private static $instance;


    /**
     * The table name
     *
     * @var string
     */
    protected $table;


    /**
     * The MetaModel object
     *
     * @var IMetaModel
     */
    protected $metaModel;


    /**
     * The database object
     *
     * @var \Contao\Database
     */
    protected $database;


    /**
     * The MetaModel's owner attribute
     *
     * @type IAttribute
     */
    protected $ownerAttribute;


    /**
     * Load the MetaModel settings
     */
    public function __construct()
    {
        $configKey = lcfirst((new \ReflectionClass($this))->getShortName());

        // Get MetaModel object
        $factory = Factory::getDefaultFactory();
        $this->metaModel = $factory->getMetaModel(FerienpassConfig::get($configKey.':model'));

        // Exit if MetaModel object could not be created
        if (null === $this->metaModel) {
            return;
        }

        // Get table name
        $this->table = $this->metaModel->getTableName();

        // Get database object
        $this->database = $this->metaModel->getServiceContainer()->getDatabase();
    }


    /**
     * Return the object instance (Singleton)
     *
     * @return static The object instance
     */
    public static function getInstance()
    {
        if (static::$instance === null) {
            $class = get_called_class();
            static::$instance = new $class();
        }

        return static::$instance;
    }


    /**
     * Return the MetaModel object
     *
     * @return \MetaModels\IMetaModel|null
     */
    public function getMetaModel()
    {
        return $this->metaModel;
    }


    /**
     * Return the owner attribute
     *
     * @return IAttribute
     */
    public function getOwnerAttribute()
    {
        $this->fetchOwnerAttribute();

        return $this->ownerAttribute;
    }


    /**
     * Find one item by its id
     *
     * @param $id
     *
     * @return \MetaModels\IItem|null
     */
    public function findById($id)
    {
        return $this->metaModel->findById($id);
    }


    /**
     * Return all items as collection
     *
     * @return \MetaModels\IItem[]|\MetaModels\IItems
     */
    public function findAll()
    {
        return $this->metaModel->findByFilter(null);
    }


    /**
     * Return a set of items by given ids as collection
     *
     * @param array $ids
     *
     * @return \MetaModels\IItem[]|\MetaModels\IItems
     */
    public function findMultipleByIds($ids)
    {
        $filter = new Filter($this->metaModel);
        $filter->addFilterRule(new StaticIdList($ids));

        return $this->metaModel->findByFilter($filter);
    }


    /**
     * Set MetaModel's owner attribute
     */
    protected function fetchOwnerAttribute()
    {
        if (null !== $this->ownerAttribute) {
            return;
        }

        $this->ownerAttribute = $this->metaModel->getAttributeById($this->metaModel->get('owner_attribute'));

        if (null === $this->ownerAttribute) {
            throw new \RuntimeException('No owner attribute in the MetaModel was found');
        }
    }
}