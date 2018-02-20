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

namespace Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\OfferDate;

use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Date\ParseDateEvent;
use Doctrine\DBAL\Connection;
use MetaModels\Attribute\BaseComplex;
use MetaModels\IMetaModel;
use MetaModels\Render\Template;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Class OfferDate
 *
 * @package MetaModels\Attribute\OfferDate
 */
class OfferDate extends BaseComplex
{

    /**
     * Database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Instantiate an MetaModel attribute.
     *
     * Note that you should not use this directly but use the factory classes to instantiate attributes.
     *
     * @param IMetaModel               $objMetaModel The MetaModel instance this attribute belongs to.
     *
     * @param array                    $arrData      The information array, for attribute information, refer to
     *                                               documentation of table tl_metamodel_attribute and documentation of
     *                                               the certain attribute classes for information what values are
     *                                               understood.
     *
     * @param Connection               $connection   The database connection.
     *
     * @param EventDispatcherInterface $dispatcher   The event dispatcher.
     */
    public function __construct(
        IMetaModel $objMetaModel,
        array $arrData = [],
        Connection $connection = null,
        EventDispatcherInterface $dispatcher = null
    ) {
        parent::__construct($objMetaModel, $arrData);

        $this->connection = $connection;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Return the table we are operating on.
     *
     * @return string
     */
    protected function getValueTable()
    {
        return 'tl_metamodel_offer_date';
    }


    /**
     * {@inheritdoc}
     */
    public function getFieldDefinition($arrOverrides = []): array
    {
        $arrFieldDef = parent::getFieldDefinition($arrOverrides);

        $arrFieldDef['inputType'] = 'offer_date';

        $arrFieldDef['eval']['columnFields'] = [];

        return $arrFieldDef;
    }


    /**
     * {@inheritdoc}
     */
    public function setDataFor($values)
    {
        // Check if we have an array.
        if (empty($values)) {
            return;
        }

        // Get the ids.
        $ids = array_keys($values);

        // Insert or update the cells.
        foreach ($ids as $id) {
            // Delete all entries as we override them
            $this->connection->createQueryBuilder()
                ->delete($this->getValueTable())
                ->where('att_id=:attr')
                ->andWhere('item_id=:item')
                ->setParameter('attr', $this->get('id'))
                ->setParameter('item', $id)
                ->execute();

            // Walk every row.
            foreach ((array)$values[$id] as $period) {
                if (null === ($setValues = $this->getSetValues($period, $id))) {
                    continue;
                }

                // Walk every column and update / insert the value.
                $this->connection->createQueryBuilder()
                    ->insert($this->getValueTable())
                    ->values($setValues)
                    ->execute();
            }
        }
    }


    /**
     * Sorts the given array list by field value in the given direction.
     *
     * @param string[] $idList    A list of Ids from the MetaModel table.
     *
     * @param string   $direction The direction for sorting. either 'ASC' or 'DESC', as in plain SQL.
     *
     * @return string[] The sorted array.
     *
     * @throws \InvalidArgumentException
     */
    public function sortIds($idList, $direction)
    {
        switch ($direction) {
            case 'ASC';
                $function = 'MIN(start)';
                break;

            case 'DESC':
                $function = 'MAX(start)';
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Invalid direction "%s" given', $direction));
        }

        $statement = $this->connection->createQueryBuilder()
            ->select(
                'item.id AS item_id',
                // The COALESCE statement will use the next variant's date for sorting when the varbase's date is not present
                "COALESCE(dateperiod.start, (SELECT $function FROM {$this->getValueTable()} WHERE item_id IN (SELECT id FROM {$this->getMetaModel()->getTableName()} WHERE vargroup=item.id))) AS sortdate"
            )
            ->from($this->getMetaModel()->getTableName(), 'item')
            ->leftJoin('item', $this->getValueTable(), 'dateperiod', 'dateperiod.item_id=item.id')
            ->where('item.id IN (:items)')
            ->groupBy('item.id')
            ->orderBy('sortdate', $direction)
            ->setParameter('items', $idList, Connection::PARAM_INT_ARRAY)
            ->execute();

        return $statement->fetchAll(\PDO::FETCH_COLUMN, 'item_id');
    }


    /**
     * Retrieve the filter options of this attribute.
     *
     * Retrieve values for use in filter options, that will be understood by DC_ filter
     * panels and frontend filter select boxes.
     * One can influence the amount of returned entries with the two parameters.
     * For the id list, the value "null" represents (as everywhere in MetaModels) all entries.
     * An empty array will return no entries at all.
     * The parameter "used only" determines, if only really attached values shall be returned.
     * This is only relevant, when using "null" as id list for attributes that have pre configured
     * values like select lists and tags i.e.
     *
     * @param string[]|null $idList   The ids of items that the values shall be fetched from
     *                                (If empty or null, all items).
     *
     * @param bool          $usedOnly Determines if only "used" values shall be returned.
     *
     * @param array|null    $arrCount Array for the counted values.
     *
     * @return array All options matching the given conditions as name => value.
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null): array
    {
        return [];
    }


    /**
     * Calculate the array of query parameters for the given cell.
     *
     * @param array $period The cell to calculate.
     *
     * @param int   $id     The data set id.
     *
     * @return array
     */
    protected function getSetValues($period, $id): ?array
    {
        if ('' === $period['start'] && '' === $period['end']) {
            return null;
        }

        return [
            'tstamp'  => time(),
            'att_id'  => $this->get('id'),
            'item_id' => $id,
            'start'   => (int)$period['start'],
            'end'     => (int)$period['end'],
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function getDataFor($ids): array
    {
        $where   = $this->getWhere($ids);
        $builder = $this->connection->createQueryBuilder()
            ->select('*')
            ->from($this->getValueTable())
            ->orderBy('start');

        if ($where) {
            $builder->andWhere($where['procedure']);

            foreach ($where['params'] as $name => $value) {
                $builder->setParameter($name, $value);
            }
        }

        $statement = $builder->execute();
        $return    = [];

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $return[$row['item_id']][] = $row;
        }

        return $return;
    }


    /**
     * @return string
     */
    public function getPeriodDelimiter(): string
    {
        return ' — ';
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
            $whereIds = \is_array($ids) ? ' AND item_id IN ('.implode(',', $ids).')' : ' AND item_id='.$ids;
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
    public function valueToWidget($value)
    {
        if (!\is_array($value)) {
            return [];
        }

        $widgetValue = [];

        foreach ($value as $i => $period) {
            $widgetValue[$i]['start'] = $period['start'];
            $widgetValue[$i]['end']   = $period['end'];
        }

        return $widgetValue;
    }


    /**
     * {@inheritdoc}
     */
    public function widgetToValue($value, $itemId)
    {
        return $value;
    }


    /**
     * {@inheritDoc}
     */
    protected function prepareTemplate(Template $template, $rowData, $settings)
    {
        parent::prepareTemplate($template, $rowData, $settings);

        $parsedDates = [];

        foreach ((array)$template->raw as $period) {
            $parsedDate = [];

            if ((new \Date($period['start']))->dayBegin !== (new \Date($period['end']))->dayBegin) {
                $parsedDate['start'] = $this->parseDate(
                    $period['start'],
                    $settings->get('timeformatStart') ?: $this->getDateTimeFormatString()
                );
                $parsedDate['end']   = $this->parseDate(
                    $period['end'],
                    $settings->get('timeformatEnd') ?: $this->getDateTimeFormatString()
                );
            } else {
                $parsedDate['start'] = $this->parseDate(
                    $period['start'],
                    $settings->get('timeformatStartEqualDay') ?: $this->getDateTimeFormatString()
                );
                $parsedDate['end']   = $this->parseDate(
                    $period['end'],
                    $settings->get('timeformatEndEqualDay') ?: $this->getDateTimeFormatString()
                );
            }

            $parsedDate['combined'] = sprintf(
                '%1$s%3$s%2$s',
                $parsedDate['start'],
                $parsedDate['end'],
                $this->getPeriodDelimiter()
            );

            $parsedDates[] = $parsedDate;
        }

        $template->parsed = $parsedDates;
    }


    /**
     * @param int    $date
     * @param string $format
     *
     * @return string
     */
    protected function parseDate($date, string $format): string
    {
        $event = new ParseDateEvent($date, $format);
        $this->dispatcher->dispatch(ContaoEvents::DATE_PARSE, $event);

        return $event->getResult();
    }


    /**
     * Retrieve the selected type or fallback to 'date' if none selected.
     *
     * @return string
     */
    public function getDateTimeFormatString(): string
    {
        global $objPage;

        $format = 'datimFormat';
        $page   = $objPage;

        if (null !== $page && $page->$format) {
            return $page->$format;
        }

        return \Config::get($format);
    }


    /**
     * Filter all values greater than the passed value.
     *
     * @param mixed $value     The value to use as lower end.
     *
     * @param bool  $inclusive If true, the passed value will be included, if false, it will be excluded.
     *
     * @return string[]|null The list of item ids of all items matching the condition or null if all match.
     *
     * @throws \InvalidArgumentException
     */
    public function filterGreaterThan($value, $inclusive = false)
    {
        return $this->getIdsFiltered($value, $inclusive ? '>=' : '>');
    }


    /**
     * Filter all values less than the passed value.
     *
     * @param mixed $value     The value to use as upper end.
     *
     * @param bool  $inclusive If true, the passed value will be included, if false, it will be excluded.
     *
     * @return string[]|null The list of item ids of all items matching the condition or null if all match.
     *
     * @throws \InvalidArgumentException
     */
    public function filterLessThan($value, $inclusive = false)
    {
        return $this->getIdsFiltered($value, $inclusive ? '<=' : '<');
    }


    /**
     * Filter all values by specified operation.
     *
     * @param int    $value     The value to use as upper end.
     *
     * @param string $operation The specified operation like greater than, lower than etc.
     *
     * @return string[] The list of item ids of all items matching the condition.
     *
     * @throws \InvalidArgumentException
     */
    private function getIdsFiltered($value, $operation): array
    {
        switch ($operation[0]) {
            case '<';
                $function = 'MAX(end)';
                break;

            case '>':
                $function = 'MIN(start)';
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Invalid operation "%s" given', $operation));
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('item_id')
            ->from($this->getValueTable())
            ->where('att_id=:attr')
            ->groupBy('item_id')
            ->having($function.' '.$operation.' '.intval($value))
            ->setParameter('attr', $this->get('id'))
            ->execute();

        return $statement->fetch(\PDO::FETCH_COLUMN, 'item_id');
    }
}
