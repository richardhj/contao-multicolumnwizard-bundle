<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
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
class Age extends BaseComplex
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
    public function searchFor($pattern)
    {
        $query = sprintf(
            'SELECT item_id FROM %1$s WHERE (lower=0 OR lower <= :age) AND (upper=0 OR upper >= :age) AND att_id = :id',
            $this->getValueTable()
        );

        $statement = $this->connection->prepare($query);
        $statement->bindValue('age', $pattern);
        $statement->bindValue('id', $this->get('id'));
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_COLUMN, 'item_id');
    }


    /**
     * {@inheritdoc}
     */
    public function getDataFor($ids)
    {
        $where   = $this->getWhere($ids);
        $builder = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->getValueTable());

        if ($where) {
            $builder->andWhere($where['procedure']);

            foreach ($where['params'] as $name => $value) {
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
    public function setDataFor($values)
    {
        if (empty($values)) {
            return;
        }

        // Get the ids
        $ids = array_keys($values);

        $database = $this->getMetaModel()->getServiceContainer()->getDatabase();

        $query       = 'INSERT INTO '.$this->getValueTable().' %s';
        $queryUpdate = 'UPDATE %s';

        // Set data
        foreach ($ids as $id) {
            $database
                ->prepare(
                    $query.' ON DUPLICATE KEY '.str_replace(
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
    protected function getSetValues($value, $id)
    {
        $lower = 0;
        $upper = 0;

        if ($value) {
            list($lower, $upper) = trimsplit(',', $value);
        }

        return [
            'tstamp'  => time(),
            'att_id'  => (int)$this->get('id'),
            'item_id' => $id,
            'lower'   => (int)$lower,
            'upper'   => (int)$upper,
        ];
    }


    /**
     * {@inheritdoc}
     *
     * Fetch filter options from foreign table.
     *
     * @todo
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
    {
        return [];
    }


    /**
     * {@inheritdoc}
     */
    public function unsetDataFor($ids)
    {
        $where = $this->getWhere($ids);

        $builder = $this->connection->createQueryBuilder()
            ->delete($this->getValueTable());

        if ($where) {
            $builder->andWhere($where['procedure']);

            foreach ($where['params'] as $name => $value) {
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
    public function getFieldDefinition($overrides = [])
    {
        $fieldDefinition                         = parent::getFieldDefinition($overrides);
        $fieldDefinition['inputType']            = 'fp_age';
        $fieldDefinition['eval']['widget_lines'] = [
            [
                'input_format'  => 'alle Ferienpass-Kinder',
                'render_format' => 'alle Ferienpass-Kinder',
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
    protected function getWhere($ids)
    {
        $whereIds = '';

        if ($ids) {
            if (is_array($ids)) {
                $whereIds = ' AND item_id IN ('.implode(',', $ids).')';
            } else {
                $whereIds = ' AND item_id='.$ids;
            }
        }

        $return = [
            'procedure' => 'att_id=?'.$whereIds,
            'params'    => [$this->get('id')],
        ];

        return $return;
    }


    /**
     * {@inheritdoc}
     */
    protected function prepareTemplate(Template $template, $rowData, $settings)
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
            $derivedSaveFormat = preg_replace('/[1-9][0-9]*/', '%s', $value);

            if ($derivedSaveFormat === $widgetLine['save_format']) {
                $checkedLine = $i;
                break;
            }
        }

        return ($checkedLine !== null) ? $checkedLine : false;
    }
}
