<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace MetaModels\Attribute\OfferDate;

use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Date\ParseDateEvent;
use MetaModels\Attribute\BaseComplex;
use MetaModels\Render\Template;


/**
 * Class OfferDate
 * @package MetaModels\Attribute\OfferDate
 */
class OfferDate extends BaseComplex
{

    /**
     * {@inheritdoc}
     */
    public function searchFor($strPattern)
    {
        return [];

        $objValue = $this
            ->getMetaModel()
            ->getServiceContainer()
            ->getDatabase()
            ->prepare(
                sprintf(
                    'SELECT DISTINCT item_id FROM %1$s WHERE value LIKE ? AND att_id = ?',
                    $this->getValueTable()
                )
            )
            ->execute(
                str_replace(array('*', '?'), array('%', '_'), $strPattern),
                $this->get('id')
            );

        return $objValue->fetchEach('item_id');
    }

//    /**
//     * {@inheritdoc}
//     */
//    public function getAttributeSettingNames()
//    {
//        return array_merge(parent::getAttributeSettingNames(), array(
//            'tabletext_cols',
//        ));
//    }

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
//        $arrColLabels                        = deserialize($this->get('tabletext_cols'), true);
        $arrFieldDef = parent::getFieldDefinition($arrOverrides);
        $arrFieldDef['inputType'] = 'offer_date';
        $arrFieldDef['eval']['columnFields'] = [];

//        $count = count($arrColLabels);
//        for ($i = 0; $i < $count; $i++) {
//            $arrFieldDef['eval']['columnFields']['col_' . $i] = array(
//                'label'     => $arrColLabels[$i]['rowLabel'],
//                'inputType' => 'text',
//                'eval'      => array(),
//            );
//            if ($arrColLabels[$i]['rowStyle']) {
//                $arrFieldDef['eval']['columnFields']['col_' . $i]['eval']['style'] =
//                    'width:' . $arrColLabels[$i]['rowStyle'];
//            }
//        }

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
        $database = $this->getMetaModel()->getServiceContainer()->getDatabase();

        // Insert or update the cells.
        foreach ($ids as $id) {

            // Delete all entries as we override them
            $database
                ->prepare(sprintf('DELETE FROM %1$s WHERE att_id=? AND item_id=?', $this->getValueTable()))
                ->execute($this->get('id'), $id);

            // Walk every row.
            foreach ((array)$values[$id] as $period) {
                // Walk every column and update / insert the value.
                $database
                    ->prepare('INSERT INTO '.$this->getValueTable().' %s')
                    ->set($this->getSetValues($period, $id))
                    ->execute();
            }
        }
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
     *
     * Fetch filter options from foreign table.
     */
    public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
    {
        return [];

        if ($idList) {
            $objRow = $this
                ->getMetaModel()
                ->getServiceContainer()
                ->getDatabase()
                ->prepare(
                    sprintf(
                        'SELECT value, COUNT(value) as mm_count
                        FROM %1$s
                        WHERE item_id IN (%2$s) AND att_id = ?
                        GROUP BY value
                        ORDER BY FIELD(id,%2$s)',
                        $this->getValueTable(),
                        $this->parameterMask($idList)
                    )
                )
                ->execute(array_merge($idList, array($this->get('id')), $idList));
        } else {
            $objRow = $this
                ->getMetaModel()
                ->getServiceContainer()
                ->getDatabase()
                ->prepare(
                    sprintf(
                        'SELECT value, COUNT(value) as mm_count
                        FROM %s
                        WHERE att_id = ?
                        GROUP BY value',
                        $this->getValueTable()
                    )
                )
                ->execute($this->get('id'));
        }

        $arrResult = array();
        while ($objRow->next()) {
            $strValue = $objRow->value;

            if (is_array($arrCount)) {
                $arrCount[$strValue] = $objRow->mm_count;
            }

            $arrResult[$strValue] = $strValue;
        }

        return $arrResult;
    }


    /**
     * {@inheritdoc}
     */
    public function getDataFor($ids)
    {
        $where = $this->getWhere($ids);
        $result = $this
            ->getMetaModel()
            ->getServiceContainer()
            ->getDatabase()
            ->prepare(
                sprintf(
                    'SELECT * FROM %1$s%2$s',
                    $this->getValueTable(),
                    ($where ? ' WHERE '.$where['procedure'] : '')
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
                    ($where ? ' WHERE '.$where['procedure'] : '')
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
            $widgetValue[$i]['end'] = $period['end'];
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
                $parsedDate['end'] = $this->parseDate(
                    $period['end'],
                    $settings->get('timeformatEnd') ?: $this->getDateTimeFormatString()
                );
            } else {
                $parsedDate['start'] = $this->parseDate(
                    $period['start'],
                    $settings->get('timeformatStartEqualDay') ?: $this->getDateTimeFormatString()
                );
                $parsedDate['end'] = $this->parseDate(
                    $period['end'],
                    $settings->get('timeformatEndEqualDay') ?: $this->getDateTimeFormatString()
                );
            }

            $parsedDate['combined'] = sprintf('%s — %s', $parsedDate['start'], $parsedDate['end']);
            $parsedDates[] = $parsedDate;
        }

        $template->parsed = $parsedDates;
    }


    protected function parseDate($date, $format)
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
        $page = $objPage;

        if (null !== $page && $page->$format) {
            return $page->$format;
        }

        return \Config::get($format);
    }


    /**
     * {@inheritdoc}
     */
    public function filterGreaterThan($value, $inclusive = false)
    {
        return $this->getIdsFiltered($value, ($inclusive) ? '>=' : '>');
    }


    /**
     * {@inheritdoc}
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
