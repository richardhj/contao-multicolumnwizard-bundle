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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\DataProcessing;


use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\BuildWidgetEvent;
use MetaModels\Filter\Setting\FilterSettingFactory;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;

/**
 * Class BuildWidgetListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\DataProcessing
 */
class BuildWidgetListener
{

    /**
     * The filter factory.
     *
     * @var FilterSettingFactory
     */
    private $filterSettingFactory;

    /**
     * BuildWidgetListener constructor.
     *
     * @param FilterSettingFactory $filterSettingFactory The filter factory.
     */
    public function __construct(FilterSettingFactory $filterSettingFactory)
    {
        $this->filterSettingFactory = $filterSettingFactory;
    }

    /**
     * Set the filter parameters when filtering is enabled.
     *
     * @param BuildWidgetEvent $event The event.
     */
    public function handle(BuildWidgetEvent $event): void
    {
        $environment = $event->getEnvironment();
        $model       = $event->getModel();
        $property    = $event->getProperty();
        $extra       = $property->getExtra();

        if ('metamodel_filterparams' !== $property->getName()
            || 'tl_ferienpass_dataprocessing' !== $environment->getDataDefinition()->getName()) {
            return;
        }

        if (!$model->getProperty('metamodel_filtering')) {
            $property->setExcluded(true);

            return;
        }

        try {
            $filterSettings = $this->filterSettingFactory
                ->createCollection($model->getProperty('metamodel_filtering'));
        } catch (\RuntimeException $e) {
            return;
        }

        $extra['subfields'] = $filterSettings->getParameterDCA();
        $property->setExtra($extra);
    }
}
