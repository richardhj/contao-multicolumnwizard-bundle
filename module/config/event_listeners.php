<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
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
