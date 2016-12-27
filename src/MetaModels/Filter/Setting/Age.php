<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace MetaModels\Filter\Setting;

use MetaModels\Filter\IFilter;
use MetaModels\Filter\Rules\SearchAttribute;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\FrontendIntegration\FrontendFilterOptions;


/**
 * Class Age
 * @package MetaModels\Filter\Setting
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
        if ($this->get('urlparam')) {
            return $this->get('urlparam');
        }

        $objAttribute = $this->getMetaModel()->getAttributeById($this->get('attr_id'));

        if ($objAttribute) {
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

        if ($objAttribute && $strParamName && $strParamValue) {
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
        if (($strParamName = $this->getParamName())) {
            return array
            (
                $strParamName => ($this->get('label') ? $this->get('label') : $this->getParamName()),
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
    ) {
        // If defined as static, return nothing as not to be manipulated via editors.
        if (!$this->enableFEFilterWidget()) {
            return [];
        }

        $arrReturn = [];
        $this->addFilterParam($this->getParamName());

        // Address search.
        $arrCount = [];
        $arrWidget = [
            'label'     => [
                ($this->get('label') ? $this->get('label') : $this->getParamName()),
                'GET: '.$this->getParamName(),
            ],
            'inputType' => 'text',
            'count'     => $arrCount,
            'showCount' => $objFrontendFilterOptions->isShowCountValues(),
            'eval'      => [
                'colname'  => $this->getMetaModel()->getAttributeById($this->get('attr_id'))->getColname(),
                'urlparam' => $this->getParamName(),
                'template' => $this->get('template'),
            ],
        ];

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
        return [];
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
