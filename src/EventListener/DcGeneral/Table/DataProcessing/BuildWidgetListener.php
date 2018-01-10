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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\DataProcessing;


use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\BuildWidgetEvent;
use MetaModels\Filter\Setting\FilterSettingFactory;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;

class BuildWidgetListener
{

    /**
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
     * @param BuildWidgetEvent $event The event.
     */
    public function handle(BuildWidgetEvent $event)
    {
        $environment = $event->getEnvironment();
        $model       = $event->getModel();
        $property    = $event->getProperty();

        if ('tl_ferienpass_dataprocessing' !== $environment->getDataDefinition()->getName()
            || 'metamodel_filterparams' !== $property->getName()) {
            return;
        }

        $element = DataProcessing::findByPk($model->getId());
        if (!$element->metamodel_filtering) {
            $property->setExcluded(true);

            return;
        }

        $extra          = $property->getExtra();
        $filterSettings = $this->filterSettingFactory
            ->createCollection($element->metamodel_filtering);

        $extra['subfields'] = $filterSettings->getParameterDCA();
        $property->setExtra($extra);
    }

}