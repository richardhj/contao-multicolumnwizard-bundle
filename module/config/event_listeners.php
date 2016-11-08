<?php
/**
 * The MetaModels extension allows the creation of multiple collections of custom items,
 * each with its own unique set of selectable attributes, with attribute extendability.
 * The Front-End modules allow you to build powerful listing and filtering of the
 * data in each collection.
 * PHP version 5
 * @package    MetaModels
 * @subpackage FilterText
 * @author     Christopher Boelter <christopher@boelter.eu>
 * @copyright  The MetaModels team.
 * @license    LGPL.
 * @filesource
 */

use MetaModels\Attribute\Age\AttributeTypeFactory as AgeAttributeTypeFactory;
use MetaModels\Attribute\Events\CreateAttributeFactoryEvent;
use MetaModels\Attribute\OfferDate\AttributeTypeFactory as OfferDateAttributeTypeFactory;
use MetaModels\Filter\Setting\AgeFilterSettingTypeFactory;
use MetaModels\Filter\Setting\AttendanceAvailableFilterSettingTypeFactory;
use MetaModels\Filter\Setting\Events\CreateFilterSettingFactoryEvent;
use MetaModels\MetaModelsEvents;


return [
    // MetaModel Attributes
    MetaModelsEvents::ATTRIBUTE_FACTORY_CREATE      => [
        // Age attribute
        function (CreateAttributeFactoryEvent $event) {
            $factory = $event->getFactory();
            $factory->addTypeFactory(new AgeAttributeTypeFactory());
        },
        // Offer date attribute
        function (CreateAttributeFactoryEvent $event) {
            $factory = $event->getFactory();
            $factory->addTypeFactory(new OfferDateAttributeTypeFactory());
        },
    ],

    // MetaModel Filters
    MetaModelsEvents::FILTER_SETTING_FACTORY_CREATE => [
        // Age filter
        function (CreateFilterSettingFactoryEvent $event) {
            $event->getFactory()->addTypeFactory(new AgeFilterSettingTypeFactory());
        },

        // Attendance available filter
        function (CreateFilterSettingFactoryEvent $event) {
            $event->getFactory()->addTypeFactory(new AttendanceAvailableFilterSettingTypeFactory());
        },
    ],
];
