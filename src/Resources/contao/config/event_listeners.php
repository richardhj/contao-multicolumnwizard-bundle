<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

use Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\Age\AttributeTypeFactory as AgeAttributeTypeFactory;
use Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\OfferDate\AttributeTypeFactory as OfferDateAttributeTypeFactory;
use Richardhj\ContaoFerienpassBundle\MetaModels\Filter\Setting\AgeFilterSettingTypeFactory;
use Richardhj\ContaoFerienpassBundle\MetaModels\Filter\Setting\AttendanceAvailableFilterSettingTypeFactory;
use Richardhj\ContaoFerienpassBundle\MetaModels\Filter\Setting\FromToOfferDateFilterSettingTypeFactory;
use MetaModels\Attribute\Events\CreateAttributeFactoryEvent;
use MetaModels\Filter\Setting\Events\CreateFilterSettingFactoryEvent;
use MetaModels\MetaModelsEvents;


return [
    // MetaModel Attributes
    MetaModelsEvents::ATTRIBUTE_FACTORY_CREATE      => [
        function (CreateAttributeFactoryEvent $event) {
            $event->getFactory()
                ->addTypeFactory(new AgeAttributeTypeFactory())
                ->addTypeFactory(new OfferDateAttributeTypeFactory());
        },
    ],

    // MetaModel Filters
    MetaModelsEvents::FILTER_SETTING_FACTORY_CREATE => [
        function (CreateFilterSettingFactoryEvent $event) {
            $event->getFactory()
                ->addTypeFactory(new AgeFilterSettingTypeFactory())
                ->addTypeFactory(new AttendanceAvailableFilterSettingTypeFactory())
                ->addTypeFactory(new FromToOfferDateFilterSettingTypeFactory());
        },
    ],
];
