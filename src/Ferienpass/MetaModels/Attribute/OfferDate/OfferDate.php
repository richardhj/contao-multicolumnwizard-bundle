<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\MetaModels\Attribute\OfferDate;

use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Date\ParseDateEvent;
use MetaModels\Attribute\BaseComplex;
use MetaModels\Render\Template;


/**
 * Class OfferDate
 *
 * @package MetaModels\Attribute\OfferDate
 */
class OfferDate extends BaseComplex
{

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
    public function getFieldDefinition($arrOverrides = [])
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
        $ids      = array_keys($values);
        $database = $this->getMetaModel()->getServiceContainer()->getDatabase();

        // Insert or update the cells.
        foreach ($ids as $id) {

            // Delete all entries as we override them
            $database
                ->prepare(sprintf('DELETE FROM %1$s WHERE att_id=? AND item_id=?', $this->getValueTable()))
                ->execute($this->get('id'), $id);

            // Walk every row.
            foreach ((array) $values[$id] as $period) {
                if (null === ($setValues = $this->getSetValues($period, $id))) {
                    continue;
                }

                // Walk every column and update / insert the value.
                $database
                    ->prepare('INSERT INTO ' . $this->getValueTable() . ' %s')
                    ->set($setValues)
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

        // The IFNULL statement will use the next variant's date for sorting when the varbase's date is not present
        $idList = $this->getMetaModel()->getServiceContainer()->getDatabase()
            ->prepare(
                sprintf(
                    <<<'SQL'
SELECT
  item.id AS item_id,
  IFNULL(
    dateperiod.start,
    (SELECT %4$s FROM %2$s WHERE item_id IN (SELECT id FROM %1$s WHERE vargroup=item.id))
  ) AS sortdate
FROM %1$s item
LEFT JOIN %2$s dateperiod ON dateperiod.item_id=item.id
WHERE item.id IN (%3$s)
GROUP BY item.id
ORDER BY sortdate %5$s
SQL
                    ,
                    $this->getMetaModel()->getTableName(),
                    $this->getValueTable(),
                    $this->parameterMask($idList),
                    $function,
                    $direction
                )
            )
            ->execute($idList)
            ->fetchEach('item_id');

        return $idList;
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
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
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
    protected function getSetValues($period, $id)
    {
        if ('' === $period['start'] && '' === $period['end']) {
            return null;
        }

        return [
            'tstamp'  => time(),
            'att_id'  => $this->get('id'),
            'item_id' => $id,
            'start'   => (int) $period['start'],
            'end'     => (int) $period['end'],
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function getDataFor($ids)
    {
        $where  = $this->getWhere($ids);
        $result = $this
            ->getMetaModel()
            ->getServiceContainer()
            ->getDatabase()
            ->prepare(
                sprintf(
                    'SELECT * FROM %1$s%2$s ORDER BY start',
                    $this->getValueTable(),
                    ($where ? ' WHERE ' . $where['procedure'] : '')
                )
            )
            ->execute(($where ? $where['params'] : null));

        $return = [];

        while ($result->next()) {
            $return[$result->item_id][] = $result->row();
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
    protected function getWhere($ids)
    {
        $whereIds = '';

        if ($ids) {
            if (is_array($ids)) {
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
    public function unsetDataFor($ids)
    {
        $where = $this->getWhere($ids);

        $this
            ->getMetaModel()
            ->getServiceContainer()
            ->getDatabase()
            ->prepare(
                sprintf(
                    'DELETE FROM %1$s%2$s',
                    $this->getValueTable(),
                    ($where ? ' WHERE ' . $where['procedure'] : '')
                )
            )
            ->execute(($where ? $where['params'] : null));
    }


    /**
     * {@inheritdoc}
     */
    public function valueToWidget($value)
    {
        if (!is_array($value)) {
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

        foreach ((array) $template->raw as $period) {
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
    protected function parseDate($date, string $format)
    {
        $dispatcher = $this->getMetaModel()->getServiceContainer()->getEventDispatcher();

        /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher */
        $event = new ParseDateEvent($date, $format);
        $dispatcher->dispatch(ContaoEvents::DATE_PARSE, $event);

        return $event->getResult();
    }


    /**
     * Retrieve the selected type or fallback to 'date' if none selected.
     *
     * @return string
     */
    public function getDateTimeFormatString()
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
     */
    public function filterGreaterThan($value, $inclusive = false)
    {
        return $this->getIdsFiltered($value, ($inclusive) ? '>=' : '>');
    }


    /**
     * Filter all values less than the passed value.
     *
     * @param mixed $value     The value to use as upper end.
     *
     * @param bool  $inclusive If true, the passed value will be included, if false, it will be excluded.
     *
     * @return string[]|null The list of item ids of all items matching the condition or null if all match.
     */
    public function filterLessThan($value, $inclusive = false)
    {
        return $this->getIdsFiltered($value, ($inclusive) ? '<=' : '<');
    }


    /**
     * Filter all values by specified operation.
     *
     * @param int    $value     The value to use as upper end.
     *
     * @param string $operation The specified operation like greater than, lower than etc.
     *
     * @return string[] The list of item ids of all items matching the condition.
     */
    private function getIdsFiltered($value, $operation)
    {
        switch (substr($operation, 0, 1)) {
            case '<';
                $function = 'MAX(end)';
                break;

            case '>':
                $function = 'MIN(start)';
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Invalid operation "%s" given', $operation));
        }

        $query = sprintf(
            'SELECT item_id FROM %s WHERE att_id=%s GROUP BY item_id HAVING %s %s %d',
            $this->getValueTable(),
            $this->get('id'),
            $function,
            $operation,
            intval($value)
        );

        $result = $this->getMetaModel()->getServiceContainer()->getDatabase()->execute($query);

        return $result->fetchEach('item_id');
    }
}
