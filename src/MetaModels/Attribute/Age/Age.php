<?php

/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 *
 * PHP version 5
 * @package    MetaModels
 * @subpackage AttributeNumeric
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author     Stefan Heimes <stefan_heimes@hotmail.com>
 * @author     Andreas Isaak <info@andreas-isaak.de>
 * @author     David Greminger <david.greminger@1up.io>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

namespace MetaModels\Attribute\Age;

use MetaModels\Attribute\BaseComplex;
use MetaModels\Render\Template;


/**
 * This is the MetaModelAttribute class for handling numeric fields.
 *
 * @package    MetaModels
 * @subpackage AttributeNumeric
 * @author     Christian Schiffler <c.schiffler@cyberspectrum.de>
 */
class Age extends BaseComplex
{

    /**
     * {@inheritdoc}
     */
    public function searchFor($pattern)
    {
        $result = $this
            ->getMetaModel()
            ->getServiceContainer()
            ->getDatabase()
            ->prepare(
                sprintf(
                    'SELECT item_id FROM %1$s WHERE (lower=0 OR lower<=?) AND (upper=0 OR upper>=?) AND att_id=?',
                    $this->getValueTable()
                )
            )
            ->execute(
                $pattern,
                $pattern,
                $this->get('id')
            );

        return $result->fetchEach('item_id');
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
            $lower = $result->lower ?: '';
            $upper = $result->upper ?: '';

            $return[$result->item_id] = ($lower === '' && $upper === '') ? 0 : sprintf('%s,%s', $lower, $upper);
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

        $query = 'INSERT INTO '.$this->getValueTable().' %s';
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
     * @todo
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
                ->execute(array_merge($idList, [$this->get('id')], $idList));
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

        $arrResult = [];

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
        $fieldDefinition = parent::getFieldDefinition($overrides);
        $fieldDefinition['inputType'] = 'fp_age';
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

        $colname = $this->getColName();
        $varValue = $rowData[$colname];

        $arrFieldDefinition = $this->getFieldDefinition();
        $arrWidgetLines = $arrFieldDefinition['eval']['widget_lines'];
        $checkedFile = $this->getCheckedLine($varValue, $arrWidgetLines);

        if (false !== $checkedFile) {
            $arrLineInputValues = array_values(array_filter(trimsplit(',', $varValue)));
            $template->parsed = vsprintf($arrWidgetLines[$checkedFile]['render_format'], $arrLineInputValues);
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
