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
	public function searchFor($strPattern)
	{
		$objValue = $this
			->getMetaModel()
			->getServiceContainer()
			->getDatabase()
			->prepare
			(
				sprintf
				(
					'SELECT item_id FROM %1$s WHERE (lower=0 OR lower<=?) AND (upper=0 OR upper>=?) AND att_id=?',
					$this->getValueTable()
				)
			)
			->execute
			(
				$strPattern,
				$strPattern,
				$this->get('id')
			);

		return $objValue->fetchEach('item_id');
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDataFor($arrIds)
	{
		$arrWhere = $this->getWhere($arrIds);
		$objValue = $this
			->getMetaModel()
			->getServiceContainer()
			->getDatabase()
			->prepare
			(
				sprintf
				(
					'SELECT * FROM %1$s%2$s',
					$this->getValueTable(),
					($arrWhere ? ' WHERE ' . $arrWhere['procedure'] : '')
				)
			)
			->execute(($arrWhere ? $arrWhere['params'] : null));

		$arrReturn = array();

		while ($objValue->next())
		{
			$lower = $objValue->lower ?: '';
			$upper = $objValue->upper ?: '';

			$arrReturn[$objValue->item_id] = ($lower === '' && $upper === '') ? 0 : sprintf('%s,%s', $lower, $upper);
		}

		return $arrReturn;
	}


	/**
	 * {@inheritdoc}
	 */
	public function setDataFor($arrValues)
	{
		if (empty($arrValues))
		{
			return;
		}

		// Get the ids
		$arrIds = array_keys($arrValues);
		$objDB  = $this->getMetaModel()->getServiceContainer()->getDatabase();

		$strQuery = 'INSERT INTO ' . $this->getValueTable() . ' %s';
		$strQueryUpdate = 'UPDATE %s';

		// Set data
		foreach ($arrIds as $intId)
		{
			$objDB
				->prepare
				(
					$strQuery .
					' ON DUPLICATE KEY ' .
					str_replace
					(
						'SET ',
						'',
						$objDB
							->prepare($strQueryUpdate)
							->set($this->getSetValues($arrValues[$intId], $intId))
							->query
					)
				)
				->set($this->getSetValues($arrValues[$intId], $intId))
				->execute();
		}
	}


	/**
	 * Calculate the array of query parameters for the given cell.
	 *
	 * @param string $strValue The widget's value as string
	 * @param int    $intId    The data set id.
	 *
	 * @return array
	 */
	protected function getSetValues($strValue, $intId)
	{
		$lower = 0;
		$upper = 0;

		if ($strValue)
		{
			list($lower, $upper) = trimsplit(',', $strValue);
		}

		return array
		(
			'tstamp'  => time(),
			'att_id'  => (int)$this->get('id'),
			'item_id' => $intId,
			'lower'   => (int)$lower,
			'upper'   => (int)$upper
		);
	}


	/**
	 * {@inheritdoc}
	 *
	 * Fetch filter options from foreign table.
	 * @todo
	 */
	public function getFilterOptions($idList, $usedOnly, &$arrCount = null)
	{
		return array();
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
	public function unsetDataFor($arrIds)
	{
		$arrWhere = $this->getWhere($arrIds);

		$this
			->getMetaModel()
			->getServiceContainer()
			->getDatabase()
			->prepare
			(
				sprintf
				(
					'DELETE FROM %1$s%2$s',
					$this->getValueTable(),
					($arrWhere ? ' WHERE ' . $arrWhere['procedure'] : '')
				)
			)
			->execute(($arrWhere ? $arrWhere['params'] : null));
	}


    /**
     * {@inheritdoc}
     */
    public function getAttributeSettingNames()
    {
        return array_merge(
            parent::getAttributeSettingNames(),
            array(
                'mandatory',
                'filterable',
                'searchable',
            )
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
    public function getFieldDefinition($arrOverrides = array())
    {
        $arrFieldDef                 = parent::getFieldDefinition($arrOverrides);
        $arrFieldDef['inputType']    = 'fp_age';
	    $arrFieldDef['eval']['widget_lines'] = array
		(
		    array
		    (
			    'input_format' => 'alle Ferienpass-Kinder',
			    'render_format' => 'alle Ferienpass-Kinder',
			    'save_format' => '0',
			    'default' => true
		    ),
			array
			(
			    'input_format' => 'Kinder ab</label> %s Jahre',
				'render_format' => 'Kinder ab %s Jahre',
			    'save_format' => '%s,'
			),
			array
			(
			    'input_format' => 'Kinder bis</label> %s Jahre',
			    'render_format' => 'Kinder bis %s Jahre',
			    'save_format' => ',%s'
			),
			array
			(
			    'input_format' => 'Kinder von</label> %s bis %s Jahre',
			    'render_format' => 'Kinder von %s bis %s Jahre',
			    'save_format' => '%s,%s'
			)
		);

        return $arrFieldDef;
    }


	/**
	 * Build a where clause for the given id(s) and rows/cols.
	 *
	 * @param mixed $mixIds One, none or many ids to use.
	 *
	 * @return array<string,string|array>
	 */
	protected function getWhere($mixIds)
	{
		$strWhereIds = '';

		if ($mixIds)
		{
			if (is_array($mixIds))
			{
				$strWhereIds = ' AND item_id IN (' . implode(',', $mixIds) . ')';
			}
			else
			{
				$strWhereIds = ' AND item_id=' . $mixIds;
			}
		}

		$arrReturn = array(
			'procedure' => 'att_id=?' . $strWhereIds,
			'params'    => array($this->get('id'))
		);

		return $arrReturn;
	}


	/**
	 * {@inheritdoc}
	 */
	protected function prepareTemplate(Template $objTemplate, $arrRowData, $objSettings)
	{
		parent::prepareTemplate($objTemplate, $arrRowData, $objSettings);

		$colname = $this->getColName();
		$varValue = $arrRowData[$colname];

		$arrFieldDefinition = $this->getFieldDefinition();
		$arrWidgetLines = $arrFieldDefinition['eval']['widget_lines'];
		$checkedFile = $this->getCheckedLine($varValue, $arrWidgetLines);

		if ($checkedFile !== false)
		{
			$arrLineInputValues = array_values(array_filter(trimsplit(',', $varValue)));
			$objTemplate->parsed = vsprintf($arrWidgetLines[$checkedFile]['render_format'], $arrLineInputValues);
		}
	}


	/**
	 * Get widget's checked line
	 *
	 * @param mixed $varValue
	 * @param array $arrWidgetLines
	 *
	 * @return false|int
	 */
	protected function getCheckedLine($varValue, $arrWidgetLines)
	{
		$intCheckedLine = null;

		foreach ($arrWidgetLines as $i=>$arrWidgetLine)
		{
			$strDerivedSaveFormat = preg_replace('/[1-9][0-9]*/', '%s', $varValue);

			if ($strDerivedSaveFormat == $arrWidgetLine['save_format'])
			{
				$intCheckedLine = $i;
				break;
			}
		}

		return ($intCheckedLine !== null) ? $intCheckedLine : false;
	}
}
