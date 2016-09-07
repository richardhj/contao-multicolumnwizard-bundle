<?php

/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 * PHP version 5
 * @package      MetaModels
 * @subpackage   FilterAge
 * @author       Christian de la Haye <service@delahaye.de>
 * @author       Andreas Isaak <info@andreas-isaak.de>
 * @author       Christian Schiffler <c.schiffler@cyberspectrum.de>
 * @author       David Molineus <mail@netzmacht.de>
 * @author       David Maack <david.maack@arcor.de>
 * @author       Stefan Heimes <stefan_heimes@hotmail.com>
 * @author       Christopher Boelter <christopher@boelter.eu>
 * @copyright    The MetaModels team.
 * @license      LGPL.
 * @filesource
 */

namespace MetaModels\Filter\Setting;

use MetaModels\Filter\IFilter;
use MetaModels\Filter\Rules\SearchAttribute;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\FrontendIntegration\FrontendFilterOptions;

/**
 * Filter "age" for FE-filtering, based on filters by the MetaModels team.
 * @package       MetaModels
 * @subpackage    FrontendFilter
 * @author        Richard Henkenjohann <richard-ferienpass@henkenjohann.me>
 */
class Age extends SimpleLookup
{
	/**
	 * Overrides the parent implementation to always return true, as this setting is always optional.
	 * @return bool true if all matches shall be returned, false otherwise.
	 */
	public function allowEmpty()
	{
		return true;
	}

	/**
	 * Overrides the parent implementation to always return true, as this setting is always available for FE filtering.
	 * @return bool true as this setting is always available.
	 */
	public function enableFEFilterWidget()
	{
		return true;
	}

	/**
	 * Retrieve the filter parameter name to react on.
	 * @return string
	 */
	protected function getParamName()
	{
		if ($this->get('urlparam'))
		{
			return $this->get('urlparam');
		}

		$objAttribute = $this->getMetaModel()->getAttributeById($this->get('attr_id'));

		if ($objAttribute)
		{
			return $objAttribute->getColName();
		}

		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function prepareRules(IFilter $objFilter, $arrFilterUrl)
	{
		$objMetaModel = $this->getMetaModel();
		$objAttribute = $objMetaModel->getAttributeById($this->get('attr_id'));
		$strParamName = $this->getParamName();
		$strParamValue = $arrFilterUrl[$strParamName];

		if ($objAttribute && $strParamName && $strParamValue)
		{
			$objFilter->addFilterRule(new SearchAttribute($objAttribute, $strParamValue));

			return;
		}

		$objFilter->addFilterRule(new StaticIdList(null));
	}

	/**
	 * {@inheritdoc}
	 */
	public function getParameters()
	{
		return ($strParamName = $this->getParamName()) ? array($strParamName) : array();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getParameterFilterNames()
	{
		if (($strParamName = $this->getParamName()))
		{
			return array
			(
				$strParamName => ($this->get('label') ? $this->get('label') : $this->getParamName())
			);
		}

		return array();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getParameterFilterWidgets(
		$arrIds,
		$arrFilterUrl,
		$arrJumpTo,
		FrontendFilterOptions $objFrontendFilterOptions
	)
	{
		// If defined as static, return nothing as not to be manipulated via editors.
		if (!$this->enableFEFilterWidget())
		{
			return array();
		}

		$arrReturn = array();
		$this->addFilterParam($this->getParamName());

		// Address search.
		$arrCount = array();
		$arrWidget = array
		(
			'label'     => array
			(
				($this->get('label') ? $this->get('label') : $this->getParamName()),
				'GET: ' . $this->getParamName()
			),
			'inputType' => 'text',
			'count'     => $arrCount,
			'showCount' => $objFrontendFilterOptions->isShowCountValues(),
			'eval'      => array
			(
				'colname'  => $this->getMetaModel()->getAttributeById($this->get('attr_id'))->getColname(),
				'urlparam' => $this->getParamName(),
				'template' => $this->get('template'),
			)
		);

		// Add filter.
		$arrReturn[$this->getParamName()] =
			$this->prepareFrontendFilterWidget($arrWidget, $arrFilterUrl, $arrJumpTo, $objFrontendFilterOptions);

		return $arrReturn;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getParameterDCA()
	{
		return array();
	}

	/**
	 * Add Param to global filter params array.
	 *
	 * @param string $strParam Name of filter param.
	 *
	 * @return void
	 * @SuppressWarnings(PHPMD.Superglobals)
	 * @SuppressWarnings(PHPMD.CamelCaseVariableName)
	 */
	private function addFilterParam($strParam)
	{
		$GLOBALS['MM_FILTER_PARAMS'][] = $strParam;
	}
}
