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

use MetaModels\Factory;
use MetaModels\Filter\Filter;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IItem;
use MetaModels\IItems;
use MetaModels\IMetaModel;


/**
 * Class AbstractSimpleMetaModel
 *
 * @package Richardhj\ContaoFerienpassBundle\Model
 */
abstract class AbstractSimpleMetaModel
{

    /**
     * @var IMetaModel
     */
    protected $metaModel;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * Load the MetaModel settings
     *
     * @param Factory $factory
     * @param string  $tableName
     */
    public function __construct(Factory $factory, string $tableName)
    {
        $this->factory   = $factory;
        $this->metaModel = $this->factory->getMetaModel($tableName);
        if (null === $this->metaModel) {
            return;
        }
    }

    /**
     * Return the MetaModel object
     *
     * @return \MetaModels\IMetaModel|null
     */
    public function getMetaModel(): IMetaModel
    {
        return $this->metaModel;
    }

    /**
     * Find one item by its id
     *
     * @param $id
     *
     * @return \MetaModels\IItem|null
     */
    public function findById(int $id): ?IItem
    {
        return $this->metaModel->findById($id);
    }

    /**
     * Return all items as collection
     *
     * @return \MetaModels\IItem[]|\MetaModels\IItems
     */
    public function findAll(): IItems
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
    public function findMultipleByIds(array $ids): IItems
    {
        $filter = new Filter($this->metaModel);
        $filter->addFilterRule(new StaticIdList($ids));

        return $this->metaModel->findByFilter($filter);
    }
}
