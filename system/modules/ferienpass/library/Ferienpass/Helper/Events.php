<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 * Copyright (c) 2015-2016 Richard Henkenjohann
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard-ferienpass@henkenjohann.me>
 */

namespace Ferienpass\Helper;


use Haste\Util\Format;
use MetaModels\Factory;

class Events extends \Controller
{

	/**
	 * All attributes possible for one event
	 *
	 * @var array
	 */
	protected static $arrEventAttributes;


	/**
	 * Define event attributes
	 */
	public function __construct()
	{
		static::$arrEventAttributes = array_keys
		(
			\DcaExtractor::getInstance('tl_calendar_events')
				->getFields()
		);

		parent::__construct();
	}


	/**
	 * Get the event attributes array
	 *
	 * @return array
	 */
	public static function getEventAttributes()
	{
		return static::$arrEventAttributes;
	}


	/**
	 * Get the event attributes array with name=>translatedName
	 *
	 * @return array
	 */
	public static function getEventAttributesTranslated()
	{
		return array_combine
		(
			static::getEventAttributes(),
			array_map
			(
				function ($field)
				{
					return Format::dcaLabel('tl_calendar_events', $field) ?: $field;
				}
				, static::getEventAttributes()
			)
		);
	}


	/**
	 * Add a MetaModel's items as events
	 *
	 * @param array   $arrEvents
	 * @param array   $arrCalendars
	 * @param integer $intCalendarRangeStart
	 * @param integer $intCalendarRangeEnd
	 * @param \Events $objModule
	 *
	 * @return array
	 */
	public function getMetaModelAsEvents($arrEvents, $arrCalendars, $intCalendarRangeStart, $intCalendarRangeEnd, $objModule)
	{
		/** @type \Model $objPage */
		global $objPage;

		// Walk each calendar selected in module
		foreach ($arrCalendars as $intCalendarId)
		{
			/** @type \Model $objCalendar */
			$objCalendar = \CalendarModel::findById($intCalendarId);

			if (!$objCalendar->addMetamodel)
			{
				continue;
			}

			// Get MetaModel object
			$objMetaModel = Factory::getDefaultFactory()->getMetaModel($objCalendar->metamodel);

			// Skip if MetaModel not found
			if (null === $objMetaModel)
			{
				continue;
			}

			$objItems = $objMetaModel->findByFilter(null); //@todo a filter should be configurable in dca

			// Walk each item in MetaModel
			while ($objItems->next())
			{
				$arrEvent = array();
				$blnAddTime = false;
				$intStart = 0;
				$intEnd = 0;

				// Walk each associated attribute
				foreach (deserialize($objCalendar->metamodelFields, true) as $attribute)
				{
					$arrEvent[$attribute['calendar_field']] = $objItems->getItem()->get($attribute['metamodel_field']);

					switch ($attribute['calendar_field'])
					{
						case 'startDate':
							$intStart = $arrEvent[$attribute['calendar_field']];
							$blnAddTime = in_array($objItems->getItem()->getAttribute($attribute['metamodel_field'])->get('timetype'), ['datim', 'time']);
							break;

						case 'endDate':
							$intEnd = $arrEvent[$attribute['calendar_field']];
							break;
					}
				}

				$intKey = date('Ymd', $intStart);
				$strDate = \Date::parse($objPage->dateFormat, $intStart);
				$strDay = $GLOBALS['TL_LANG']['DAYS'][date('w', $intStart)];
				$strMonth = $GLOBALS['TL_LANG']['MONTHS'][(date('n', $intStart)-1)];

				$span = \Calendar::calculateSpan($intStart, $intEnd);

				if ($span > 0)
				{
					$strDate = \Date::parse($objPage->dateFormat, $intStart) . ' – ' . \Date::parse($objPage->dateFormat, $intEnd);
					$strDay = '';
				}

				$strTime = '';

				if ($blnAddTime)
				{
					if ($span > 0)
					{
						$strDate = \Date::parse($objPage->datimFormat, $intStart) . ' – ' . \Date::parse($objPage->datimFormat, $intEnd);
					}
					elseif ($intStart == $intEnd)
					{
						$strTime = \Date::parse($objPage->timeFormat, $intStart);
					}
					else
					{
						$strTime = \Date::parse($objPage->timeFormat, $intStart) . ' – ' . \Date::parse($objPage->timeFormat, $intEnd);
					}
				}

				// Overwrite some settings
				$arrEvent['date'] = $strDate;
				$arrEvent['time'] = $strTime;
				$arrEvent['datetime'] = $blnAddTime ? date('Y-m-d\TH:i:sP', $intStart) : date('Y-m-d', $intStart);
				$arrEvent['day'] = $strDay;
				$arrEvent['month'] = $strMonth;
				$arrEvent['calendar'] = $objCalendar;
				$arrEvent['link'] = $arrEvent['title'];
				$arrEvent['target'] = '';
				$arrEvent['title'] = specialchars($arrEvent['title'], true);
//				$arrEvent['href'] = $this->generateEventUrl($objEvents, $strUrl);
				$arrEvent['class'] = ($arrEvent['cssClass'] != '') ? ' ' . $arrEvent['cssClass'] : '';
//				$arrEvent['recurring'] = $recurring;
//				$arrEvent['until'] = $until;
				$arrEvent['begin'] = $intStart;
				$arrEvent['end'] = $intEnd;
				$arrEvent['details'] = '';
				$arrEvent['hasDetails'] = false;
				$arrEvent['hasTeaser'] = false;

				// Add event to global array
				$arrEvents[$intKey][$intStart][] = $arrEvent;
			}
		}

		return $arrEvents;
	}
}