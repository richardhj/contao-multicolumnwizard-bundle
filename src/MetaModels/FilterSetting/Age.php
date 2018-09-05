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

namespace Richardhj\ContaoFerienpassBundle\MetaModels\FilterSetting;

use MetaModels\Attribute\IAttribute;
use MetaModels\Filter\IFilter;
use MetaModels\Filter\Rules\SearchAttribute;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\Filter\Setting\SimpleLookup;
use MetaModels\FrontendIntegration\FrontendFilterOptions;


/**
 * Class Age
 *
 * @package MetaModels\Filter\Setting
 */
final class Age extends SimpleLookup
{

    /**
     * Overrides the parent implementation to always return true, as this setting is always optional.
     *
     * @return bool true if all matches shall be returned, false otherwise.
     */
    public function allowEmpty(): bool
    {
        return true;
    }

    /**
     * Overrides the parent implementation to always return true, as this setting is always available for FE filtering.
     *
     * @return bool true as this setting is always available.
     */
    public function enableFEFilterWidget(): bool
    {
        return true;
    }

    /**
     * Retrieve the attribute we are filtering on.
     *
     * @return IAttribute|null
     */
    protected function getFilteredAttribute(): ?IAttribute
    {
        return $this->getMetaModel()->getAttribute('age');
    }

    /**
     * Tells the filter setting to add all of its rules to the passed filter object.
     *
     * The filter rules can evaluate the also passed filter url.
     *
     * A filter url hereby is a simple hash of name => value layout, it may eventually be interpreted
     * by attributes via IMetaModelAttribute::searchFor() method.
     *
     * @param IFilter  $filter       The filter to append the rules to.
     *
     * @param string[] $filterValues The parameters to evaluate.
     *
     * @return void
     */
    public function prepareRules(IFilter $filter, $filterValues): void
    {
        $attribute  = $this->getFilteredAttribute();
        $paramName  = $this->getParamName();
        $paramValue = $filterValues[$paramName];

        if (null !== $attribute && null !== $paramName && $paramValue) {
            $filter->addFilterRule(new SearchAttribute($attribute, $paramValue));

            return;
        }

        $filter->addFilterRule(new StaticIdList(null));
    }

    /**
     * Retrieve a list of all registered parameters from the setting.
     *
     * @return array
     */
    public function getParameters(): array
    {
        return ($paramName = $this->getParamName()) ? [$paramName] : [];
    }

    /**
     * Retrieve the names of all parameters for listing in frontend filter configuration.
     *
     * @return string[] the parameters as array. parametername => label
     */
    public function getParameterFilterNames(): array
    {
        if ($paramName = $this->getParamName()) {
            return [
                $paramName => $this->get('label') ?: $this->getParamName(),
            ];
        }

        return [];
    }

    /**
     * Retrieve a list of filter widgets for all registered parameters as form field arrays.
     *
     * @param string[]|null         $ids                   The ids matching the current filter values.
     *
     * @param array                 $filterParams          The current filter url.
     *
     * @param array                 $jumpTo                The jumpTo page (array, row data from tl_page).
     *
     * @param FrontendFilterOptions $frontendFilterOptions The frontend filter options.
     *
     * @return array
     */
    public function getParameterFilterWidgets(
        $ids,
        $filterParams,
        $jumpTo,
        FrontendFilterOptions $frontendFilterOptions
    ): array {
        // If defined as static, return nothing as not to be manipulated via editors.
        if (!$this->enableFEFilterWidget()) {
            return [];
        }

        $return = [];
        $this->addFilterParam($this->getParamName());
        $attribute = $this->getFilteredAttribute();

        // Address search.
        $count  = [];
        $widget = [
            'label'     => [
                $this->get('label') ?: $this->getParamName(),
                'GET: ' . $this->getParamName(),
            ],
            'inputType' => 'text',
            'count'     => $count,
            'showCount' => $frontendFilterOptions->isShowCountValues(),
            'eval'      => [
                'colname'  => $attribute ? $attribute->getColName() : '',
                'urlparam' => $this->getParamName(),
                'template' => $this->get('template'),
            ],
        ];

        // Add filter.
        $return[$this->getParamName()] =
            $this->prepareFrontendFilterWidget($widget, $filterParams, $jumpTo, $frontendFilterOptions);

        return $return;
    }


    /**
     * Retrieve a list of all registered parameters from the setting as DCA compatible arrays.
     *
     * These parameters may be overridden by modules and content elements and the like.
     *
     * @return array
     */
    public function getParameterDCA(): array
    {
        return [];
    }


    /**
     * Add Param to global filter params array.
     *
     * @param string $param Name of filter param.
     *
     * @return void
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    private function addFilterParam($param): void
    {
        /** @noinspection UnsupportedStringOffsetOperationsInspection */
        $GLOBALS['MM_FILTER_PARAMS'][] = $param;
    }
}
