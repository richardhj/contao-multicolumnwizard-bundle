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

namespace Richardhj\ContaoFerienpassBundle\MetaModels\EventListener;

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use MetaModels\Filter\Setting\FilterSettingFactory;


/**
 * Class AllowFromToOfferDateListener
 *
 * @package Richardhj\ContaoFerienpassBundle\MetaModels\EventListener
 */
class AllowFromToOfferDateListener
{

    /**
     * @var FilterSettingFactory
     */
    private $filterFactory;

    public function __construct(FilterSettingFactory $filterFactory)
    {
        $this->filterFactory = $filterFactory;
    }

    public function handle(GetPropertyOptionsEvent $event): void
    {
        if (('attr_id' !== $event->getPropertyName())
            || ('tl_metamodel_filtersetting' !== $event->getEnvironment()->getDataDefinition()->getName())) {
            return;
        }

        $filterSettingTypeFactory = $this->filterFactory->getTypeFactory('fromtodate');
        if (null === $filterSettingTypeFactory) {
            return;
        }

        $filterSettingTypeFactory->addKnownAttributeType('offer_date');
    }
}
