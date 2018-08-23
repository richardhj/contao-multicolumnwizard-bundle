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

use Contao\Input;
use Contao\System;
use MetaModels\Filter\IFilter;
use MetaModels\Filter\Setting\SimpleLookup;
use MetaModels\FrontendIntegration\FrontendFilterOptions;
use MetaModels\Filter\Rules\StaticIdList as FilterRuleStaticIdList;
use MetaModels\Filter\Rules\SearchAttribute as FilterRuleSimpleLookup;
use Richardhj\ContaoFerienpassBundle\Entity\PassEdition as PassEditionEntity;


/**
 * Class PassEdition
 *
 * This is basically the simple lookup (or select) filter setting with the exception to automatically set the default
 * value to the current pass_release for the host to edit.
 *
 * @package Richardhj\ContaoFerienpassBundle\MetaModels\FilterSetting
 */
class PassEdition extends SimpleLookup
{

    /**
     * Determine if this filter setting shall return all matches if no url param has been specified.
     *
     * @return bool true if all matches shall be returned, false otherwise.
     */
    public function allowEmpty(): bool
    {
        return false;
    }

    /**
     * Determine if this filter setting shall be available for frontend filter widget generating.
     *
     * @return bool true if available, false otherwise.
     */
    public function enableFEFilterWidget(): bool
    {
        return true;
    }

    /**
     * Tells the filter setting to add all of its rules to the passed filter object.
     *
     * The filter rules can evaluate the also passed filter url.
     *
     * A filter url hereby is a simple hash of name => value layout, it may eventually be interpreted
     * by attributes via IMetaModelAttribute::searchFor() method.
     *
     * @param IFilter  $objFilter    The filter to append the rules to.
     *
     * @param string[] $arrFilterUrl The parameters to evaluate.
     *
     * @return void
     */
    public function prepareRules(IFilter $objFilter, $arrFilterUrl): void
    {
        $metaModel = $this->getMetaModel();
        $attribute = $this->getFilteredAttribute();
        $paramName = $this->getParamName();

        if ($attribute && $paramName) {
            if ($arrFilterValue = $this->determineFilterValue($arrFilterUrl, $paramName)) {
                if ($metaModel->isTranslated() && $this->get('all_langs')) {
                    $arrLanguages = $metaModel->getAvailableLanguages();
                } else {
                    $arrLanguages = array($metaModel->getActiveLanguage());
                }
                $objFilterRule = new FilterRuleSimpleLookup($attribute, $arrFilterValue, $arrLanguages);
                $objFilter->addFilterRule($objFilterRule);
                return;
            }

            // We found an attribute but no match in URL. So ignore this filter setting if allow_empty is set.
            if ($this->allowEmpty()) {
                $objFilter->addFilterRule(new FilterRuleStaticIdList(null));
                return;
            }
        }

        // Either no attribute found or no match in url, do not return anything.
        $objFilter->addFilterRule(new FilterRuleStaticIdList(array()));
    }

    /**
     * Retrieve a list of filter widgets for all registered parameters as form field arrays.
     *
     * @param string[]|null         $ids                   The ids matching the current filter values.
     *
     * @param array                 $filterUrl             The current filter url.
     *
     * @param array                 $jumpTo                The jumpTo page (array, row data from tl_page).
     *
     * @param FrontendFilterOptions $frontendFilterOptions The frontend filter options.
     *
     * @return array
     */
    public function getParameterFilterWidgets(
        $ids,
        $filterUrl,
        $jumpTo,
        FrontendFilterOptions $frontendFilterOptions
    ): array {
        // If defined as static, return nothing as not to be manipulated via editors.
        if (false === $this->enableFEFilterWidget()) {
            return [];
        }

        if (null === ($attribute = $this->getFilteredAttribute())) {
            return [];
        }

        $GLOBALS['MM_FILTER_PARAMS'][] = $this->getParamName();

        $count  = [];
        $widget = [
            'label'     => [
                $this->getLabel(),
                'GET: ' . $this->getParamName()
            ],
            'inputType' => 'select',
            'options'   => $this->getParameterFilterOptions($attribute, $ids, $count),
            'count'     => $count,
            'showCount' => $frontendFilterOptions->isShowCountValues(),
            'eval'      => [
                'includeBlankOption' => $this->get('blankoption') && !$frontendFilterOptions->isHideClearFilter(),
                'blankOptionLabel'   => &$GLOBALS['TL_LANG']['metamodels_frontendfilter']['do_not_filter'],
                'colname'            => $attribute->getColName(),
                'urlparam'           => $this->getParamName(),
                'onlyused'           => $this->get('onlyused'),
                'onlypossible'       => $this->get('onlypossible'),
                'template'           => $this->get('template'),
            ]
        ];

        $filterUrl[$this->getParamName()] = $this->determineFilterValue($filterUrl, $this->getParamName());

        return [
            $this->getParamName() => $this->prepareFrontendFilterWidget(
                $widget,
                $filterUrl,
                $jumpTo,
                $frontendFilterOptions
            )
        ];
    }

    /**
     * Determine the filter value from the passed values.
     *
     * @param array  $filterValues The filter values.
     * @param string $valueName    The parameter name to obtain.
     *
     * @return mixed|null
     */
    private function determineFilterValue($filterValues, $valueName)
    {
        if (!$filterValues[$valueName]) {
            $doctrine = System::getContainer()->get('doctrine');

            $passEdition = $doctrine->getRepository(PassEditionEntity::class)->findDefaultPassEditionForHost();
            if ($passEdition instanceof PassEditionEntity) {
                return $passEdition->getId();
            }
        }

        return $filterValues[$valueName];
    }
}
