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

namespace Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\Age;

use Doctrine\DBAL\Connection;
use MetaModels\Attribute\BaseComplex;
use MetaModels\IMetaModel;
use MetaModels\Render\Template;


/**
 * Class Age
 *
 * @package MetaModels\Attribute\Age
 */
final class Age extends BaseComplex
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
     * {@inheritdoc}
     */
    public function searchFor($pattern): ?array
    {
        $qb = $this->connection->createQueryBuilder();

        $statement = $qb
            ->select('item_id')
            ->from($this->getValueTable())
            ->where($qb->expr()->orX()->add('lower = 0')->add('lower <= :age'))
            ->andWhere($qb->expr()->orX()->add('upper = 0')->add('upper >= :age'))
            ->andWhere('att_id = :id')
            ->setParameter('age', $pattern)
            ->setParameter('id', $this->get('id'))
            ->execute();

        return $statement->fetchAll(\PDO::FETCH_COLUMN);
    }


    /**
     * {@inheritdoc}
     */
    public function getDataFor($ids): array
    {
        $where   = $this->getWhere($ids);
        $builder = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->getValueTable());

        if ($where) {
            $builder->andWhere($where['procedure']);

            foreach ((array) $where['params'] as $name => $value) {
                $builder->setParameter($name, $value);
            }
        }

        $statement = $builder->execute();
        $return    = [];

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $lower = $row['lower'] ?: '';
            $upper = $row['upper'] ?: '';

            $return[$row['item_id']] = ($lower === '' && $upper === '') ? 0 : sprintf('%s,%s', $lower, $upper);
        }

        return $return;
    }


    /**
     * {@inheritdoc}
     */
    public function setDataFor($values): void
    {
        if (empty($values)) {
            return;
        }

        // Get the ids
        $ids = array_keys($values);

        $database = $this->getMetaModel()->getServiceContainer()->getDatabase();

        // insert into tl_metamodel_age … on duplicate key update

        $query       = 'INSERT INTO ' . $this->getValueTable() . ' %s';
        $queryUpdate = 'UPDATE %s';

        // Set data
        foreach ($ids as $id) {
            $database
                ->prepare(
                    $query . ' ON DUPLICATE KEY ' . str_replace(
                        'SET ',
                        '',
                        $database
                            ->prepare($queryUpdate)
                            ->set($this->getSetValues($values[$id], $id))
                            ->query
                    )
                )
                ->set($this->getSetValues($values[$id], $id))
                ->execute();
        }
    }


    /**
     * Calculate the array of query parameters for the given cell.
     *
     * @param string $value The widget's value as string
     * @param int    $id    The data set id.
     *
     * @return array
     */
    protected function getSetValues($value, $id): array
    {
        $lower = 0;
        $upper = 0;

        if ($value) {
            [$lower, $upper] = trimsplit(',', $value);
        }

        return [
            'tstamp'  => time(),
            'att_id'  => (int) $this->get('id'),
            'item_id' => $id,
            'lower'   => $lower,
            'upper'   => $upper,
        ];
    }


    /**
     * {@inheritdoc}
     *
     * Fetch filter options from foreign table.
     *
     * @todo
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null): array
    {
        return [];
    }


    /**
     * {@inheritdoc}
     */
    public function unsetDataFor($ids): void
    {
        $where = $this->getWhere($ids);

        $builder = $this->connection->createQueryBuilder()
            ->delete($this->getValueTable());

        if ($where) {
            $builder->andWhere($where['procedure']);

            foreach ((array) $where['params'] as $name => $value) {
                $builder->setParameter($name, $value);
            }
        }

        $builder->execute();
    }


    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return array_merge(
            parent::getAttributeSettingNames(),
            [
                'mandatory',
                'filterable',
                'searchable',
            ]
        );
    }


    /**
     * Return the table we are operating on.
     *
     * @return string
     */
    protected function getValueTable()
    {
        return 'tl_metamodel_age';
    }


    /**
     * {@inheritdoc}
     * @todo
     */
    public function getFieldDefinition($overrides = []): array
    {
        $fieldDefinition                         = parent::getFieldDefinition($overrides);
        $fieldDefinition['inputType']            = 'fp_age';
        $fieldDefinition['eval']['widget_lines'] = [
            [
                'input_format'  => 'keine Altersbeschränkung',
                'render_format' => 'keine Altersbeschränkung',
                'save_format'   => '0',
                'default'       => true,
            ],
            [
                'input_format'  => 'Kinder ab</label> %s Jahre',
                'render_format' => 'Kinder ab %s Jahre',
                'save_format'   => '%s,',
            ],
            [
                'input_format'  => 'Kinder bis</label> %s Jahre',
                'render_format' => 'Kinder bis %s Jahre',
                'save_format'   => ',%s',
            ],
            [
                'input_format'  => 'Kinder von</label> %s bis %s Jahre',
                'render_format' => 'Kinder von %s bis %s Jahre',
                'save_format'   => '%s,%s',
            ],
        ];

        return $fieldDefinition;
    }


    /**
     * Build a where clause for the given id(s) and rows/cols.
     *
     * @param mixed $ids One, none or many ids to use.
     *
     * @return array<string,string|array>
     */
    protected function getWhere($ids): array
    {
        $whereIds = '';

        if ($ids) {
            if (\is_array($ids)) {
                $whereIds = ' AND item_id IN (' . implode(',', $ids) . ')';
            } else {
                $whereIds = ' AND item_id=' . $ids;
            }
        }

        $return = [
            'procedure' => 'att_id=?' . $whereIds,
            'params'    => [$this->get('id')],
        ];

        return $return;
    }


    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(Template $template, $rowData, $settings): void
    {
        parent::prepareTemplate($template, $rowData, $settings);

        $colname  = $this->getColName();
        $varValue = $rowData[$colname];

        $arrFieldDefinition = $this->getFieldDefinition();
        $arrWidgetLines     = $arrFieldDefinition['eval']['widget_lines'];
        $checkedFile        = $this->getCheckedLine($varValue, $arrWidgetLines);

        if (false !== $checkedFile) {
            $arrLineInputValues = array_values(array_filter(trimsplit(',', $varValue)));
            $template->parsed   = vsprintf($arrWidgetLines[$checkedFile]['render_format'], $arrLineInputValues);
        }
    }


    /**
     * Get widget's checked line
     *
     * @param mixed $value
     * @param array $widgetLines
     *
     * @return false|int
     */
    protected function getCheckedLine($value, $widgetLines)
    {
        $checkedLine = null;

        foreach ($widgetLines as $i => $widgetLine) {
            $derivedSaveFormat = preg_replace('/[1-9]\d*/', '%s', $value);

            if ($derivedSaveFormat === $widgetLine['save_format']) {
                $checkedLine = $i;
                break;
            }
        }

        return $checkedLine ?? false;
    }
}
