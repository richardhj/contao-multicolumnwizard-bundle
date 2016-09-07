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

use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\EncodePropertyValueFromWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ModelToLabelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PostPersistModelEvent;
use MetaModels\Filter\Setting\Events\CreateFilterSettingFactoryEvent;
use MetaModels\MetaModelsEvents;
use MetaModels\Attribute\Events\CreateAttributeFactoryEvent;

use MetaModels\Attribute\Age\AttributeTypeFactory;
use MetaModels\Filter\Setting\AgeFilterSettingTypeFactory;
use MetaModels\Filter\Setting\AttendanceAvailableFilterSettingTypeFactory;


return array
(
	// MetaModel Attributes
	MetaModelsEvents::ATTRIBUTE_FACTORY_CREATE      => array
	(
		// Age attribute
		function (CreateAttributeFactoryEvent $event)
		{
			$factory = $event->getFactory();
			$factory->addTypeFactory(new AttributeTypeFactory());
		}
	),

	// MetaModel Filters
	MetaModelsEvents::FILTER_SETTING_FACTORY_CREATE => array
	(
		// Age filter
		function (CreateFilterSettingFactoryEvent $event)
		{
			$event->getFactory()->addTypeFactory(new AgeFilterSettingTypeFactory());
		},

		// Attendance available filter
		function (CreateFilterSettingFactoryEvent $event)
		{
			$event->getFactory()->addTypeFactory(new AttendanceAvailableFilterSettingTypeFactory());
		}
	),

	// List View Label
	ModelToLabelEvent::NAME => array
	(
		array(array('Ferienpass\Helper\Dca', 'addMemberEditLinkForParticipantListView'), -10)
	),
	
	// On Submit Offer Sync
	PostPersistModelEvent::NAME => array
	(
		array('Ferienpass\Helper\Dca', 'triggerSyncForOffer')
	),

	// Trigger attendance status change
	EncodePropertyValueFromWidgetEvent::NAME => array
	(
		array('Ferienpass\Helper\Dca', 'triggerAttendanceStatusChange')
	),
);
