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
use MetaModels\Filter\Setting\SimpleLookup;
use MetaModels\FrontendIntegration\FrontendFilterOptions;
use MetaModels\Filter\Rules\StaticIdList as FilterRuleStaticIdList;
use MetaModels\Filter\Rules\SearchAttribute as FilterRuleSimpleLookup;
use Richardhj\ContaoFerienpassBundle\Entity\PassEdition as PassEditionEntity;
use Symfony\Bridge\Doctrine\ManagerRegistry;


/**
 * Class PassEdition
 *
 * This is basically the simple lookup (or select) filter setting with the exception to automatically set the default
 * value to the current pass_release for the frontend list or the host to edit.
 *
 * @package Richardhj\ContaoFerienpassBundle\MetaModels\FilterSetting
 */
final class PassEdition extends SimpleLookup
{

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    public function __construct($collection, $data, ManagerRegistry $doctrine)
    {
        parent::__construct($collection, $data);

        $this->doctrine = $doctrine;
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
     * Retrieve the attribute we are filtering on.
     *
     * @return IAttribute|null
     */
    protected function getFilteredAttribute(): ?IAttribute
    {
        return $this->getMetaModel()->getAttribute('pass_edition');
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
        $metaModel = $this->getMetaModel();
        $attribute = $this->getFilteredAttribute();
        $paramName = $this->getParamName();

        if (null !== $attribute && null !== $paramName) {
            if ($filterValue = $this->determineFilterValue($filterValues, $paramName)) {
                if ($metaModel->isTranslated() && $this->get('all_langs')) {
                    $languages = $metaModel->getAvailableLanguages();
                } else {
                    $languages = array($metaModel->getActiveLanguage());
                }

                $filterRule = new FilterRuleSimpleLookup($attribute, $filterValue, $languages);
                $filter->addFilterRule($filterRule);

                return;
            }

            // We found an attribute but no match in URL. So ignore this filter setting if allow_empty is set.
            if ($this->allowEmpty()) {
                $filter->addFilterRule(new FilterRuleStaticIdList(null));

                return;
            }
        }

        // Either no attribute found or no match in url, do not return anything.
        $filter->addFilterRule(new FilterRuleStaticIdList(array()));
    }

    /**
     * Retrieve a list of filter widgets for all registered parameters as form field arrays.
     *
     * @param string[]|null         $ids                   The ids matching the current filter values.
     *
     * @param array                 $filterValues          The current filter url.
     *
     * @param array                 $jumpTo                The jumpTo page (array, row data from tl_page).
     *
     * @param FrontendFilterOptions $frontendFilterOptions The frontend filter options.
     *
     * @return array
     */
    public function getParameterFilterWidgets(
        $ids,
        $filterValues,
        $jumpTo,
        FrontendFilterOptions $frontendFilterOptions
    ): array {
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

        $filterValues[$this->getParamName()] = $this->determineFilterValue($filterValues, $this->getParamName());

        return [
            $this->getParamName() => $this->prepareFrontendFilterWidget(
                $widget,
                $filterValues,
                $jumpTo,
                $frontendFilterOptions
            )
        ];
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
        // The filter value is not overridable in the module.
        return [];
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
        if (!$filterValues[$valueName] && $ferienpassTask = $this->get('ferienpass_task')) {
            $passEdition = null;

            switch ($ferienpassTask) {
                case 'show_offers':
                    $passEdition = $this->doctrine->getRepository(PassEditionEntity::class)->findOneToShowInFrontend();
                    break;

                case 'host_editing':
                    $passEdition =
                        $this->doctrine->getRepository(PassEditionEntity::class)->findDefaultPassEditionForHost();
                    break;
            }

            if ($passEdition instanceof PassEditionEntity) {
                return $passEdition->getId();
            }
        }

        return $filterValues[$valueName];
    }
}
