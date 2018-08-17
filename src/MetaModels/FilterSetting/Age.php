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

namespace Richardhj\ContaoFerienpassBundle\MetaModels\FilterSetting;

use MetaModels\Filter\IFilter;
use MetaModels\Filter\Rules\SearchAttribute;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\Filter\Setting\SimpleLookup;
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
    public function allowEmpty(): bool
    {
        return true;
    }

    /**
     * Overrides the parent implementation to always return true, as this setting is always available for FE filtering.
     * @return bool true as this setting is always available.
     */
    public function enableFEFilterWidget(): bool
    {
        return true;
    }

    /**
     * Retrieve the filter parameter name to react on.
     * @return string
     */
    protected function getParamName(): string
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
    public function prepareRules(IFilter $objFilter, $arrFilterUrl): void
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
    public function getParameters(): array
    {
        return ($strParamName = $this->getParamName()) ? [$strParamName] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterFilterNames()
    {
        if ($strParamName = $this->getParamName()) {
            return [
                $strParamName => $this->get('label') ? $this->get('label') : $this->getParamName(),
            ];
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterFilterWidgets(
        $arrIds,
        $arrFilterUrl,
        $arrJumpTo,
        FrontendFilterOptions $objFrontendFilterOptions
    ): array {
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
                $this->get('label') ? $this->get('label') : $this->getParamName(),
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
    public function getParameterDCA(): array
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
    private function addFilterParam($strParam): void
    {
        $GLOBALS['MM_FILTER_PARAMS'][] = $strParam;
    }
}