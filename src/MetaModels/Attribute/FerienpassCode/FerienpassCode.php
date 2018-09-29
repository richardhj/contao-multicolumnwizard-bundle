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

namespace Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\FerienpassCode;

use Doctrine\DBAL\Connection;
use MetaModels\Attribute\BaseComplex;
use MetaModels\IMetaModel;

/**
 * Class FerienpassCode
 *
 * @package Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\FerienpassCode
 */
final class FerienpassCode extends BaseComplex
{

    /**
     * Database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * Instantiate an MetaModel attribute.
     *
     * Note that you should not use this directly but use the factory classes to instantiate attributes.
     *
     * @param IMetaModel      $objMetaModel The MetaModel instance this attribute belongs to.
     *
     * @param array           $arrData      The information array, for attribute information, refer to documentation of
     *                                      table tl_metamodel_attribute and documentation of the certain attribute
     *                                      classes for information what values are understood.
     *
     * @param Connection|null $connection   The database connection.
     */
    public function __construct(IMetaModel $objMetaModel, array $arrData = [], Connection $connection = null)
    {
        parent::__construct($objMetaModel, $arrData);

        $this->connection = $connection;
    }

    /**
     * The table we are operating on.
     *
     * @return string
     */
    public function getValueTable(): string
    {
        return 'tl_ferienpass_code';
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($overrides = []): array
    {
        $fieldDefinition = parent::getFieldDefinition($overrides);

        $fieldDefinition['inputType'] = 'text';

        return $fieldDefinition;
    }

    /**
     * This method is called to redeem the code for certain items to the database.
     *
     * @param string[] $values The codes to be stored into database. Mapping is item id=>code.
     *
     * @return void
     */
    public function setDataFor($values): void
    {
        $time = time();
        foreach ($values as $itemId => $value) {
            $this->connection->createQueryBuilder()
                ->update($this->getValueTable(), 'c')
                ->set('item_id', $itemId)
                ->set('att_id', $this->get('id'))
                ->set('activated', $time)
                ->where('code=:code')
                ->setParameter('code', $value)
                ->execute();
        }
    }

    /**
     * Check whether code is valid prior persisting.
     *
     * @param mixed  $value  The value to be transformed.
     *
     * @param string $itemId The id of the item the value belongs to.
     *
     * @return mixed The resulting native value.
     */
    public function widgetToValue($value, $itemId)
    {
        $expr = $this->connection->getExpressionBuilder();

        $statement = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('tl_ferienpass_code')
            ->where('code=:code')
            ->andWhere(
                $expr->orX()
                    ->add($expr->andX()->add('activated=0'))
                    ->add($expr->andX()->add('activated<>0')->add('item_id=:item')->add('att_id=:attr'))
            )
            ->setParameter('code', $value)
            ->setParameter('item', $itemId)
            ->setParameter('attr', $this->get('id'))
            ->execute();

        if ($statement->rowCount() === 1) {
            return $value;
        }

        throw new \RuntimeException('Der eingegeben Code ist ungültig oder bereits eingelöst worden.');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null): array
    {
        return [];
    }

    /**
     * Retrieve the (redeemed) codes for given item ids from the database.
     *
     * @param string[] $ids The ids of the items to retrieve.
     *
     * @return string[] The codes. Mapping is item id=>code.
     */
    public function getDataFor($ids): array
    {
        $return = [];

        $statement = $this->connection->createQueryBuilder()
            ->select('item_id', 'code')
            ->from($this->getValueTable())
            ->where('item_id IN (:items)')
            ->andWhere('att_id=:attr')
            ->andWhere('activated<>0')
            ->setParameter('attr', $this->get('id'))
            ->setParameter('items', $ids, Connection::PARAM_STR_ARRAY)
            ->execute();

        while ($row = $statement->fetch(\PDO::FETCH_OBJ)) {
            $return[$row->item_id] = $row->code;
        }

        return $return;
    }

    /**
     * Remove values for items.
     * Codes are not meant to be revoke once redeemed.
     *
     * @param string[] $ids The ids of the items to delete.
     *
     * @return void
     */
    public function unsetDataFor($ids): void
    {
        // This is a No-Op.
    }
}
