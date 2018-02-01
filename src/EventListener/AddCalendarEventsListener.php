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

namespace Richardhj\ContaoFerienpassBundle\EventListener;

use Contao\Calendar as ContaoCalendar;
use Contao\CalendarModel;
use Contao\DcaExtractor;
use Contao\Date;
use Haste\Util\Format;
use MetaModels\Factory;
use Richardhj\ContaoFerienpassBundle\Helper\ToolboxOfferDate;


/**
 * Class Events
 *
 * @package Richardhj\ContaoFerienpassBundle\Helper
 */
class AddCalendarEventsListener
{

    /**
     * @var Factory
     */
    private $metaModelsFactory;

    /**
     * All attributes possible for one event
     *
     * @var array
     */
    protected static $eventAttributes;

    /**
     * Define event attributes
     *
     * @param Factory $factory
     */
    public function __construct(Factory $factory)
    {
        static::$eventAttributes = array_keys(DcaExtractor::getInstance('tl_calendar_events')->getFields());

        $this->metaModelsFactory = $factory;
    }

    /**
     * Get the event attributes array with name=>translatedName
     *
     * @return array
     */
    public static function getEventAttributesTranslated(): array
    {
        return array_combine(
            static::getEventAttributes(),
            array_map(
                function ($field) {
                    return Format::dcaLabel('tl_calendar_events', $field) ?: $field;
                }
                ,
                static::getEventAttributes()
            )
        );
    }

    /**
     * Get the event attributes array
     *
     * @return array
     */
    public static function getEventAttributes(): array
    {
        return static::$eventAttributes;
    }


    /**
     * Add a MetaModel's items as events
     *
     * @param array $events
     * @param array $calendars
     *
     * @return array
     *
     * @internal param int $calendarRangeStart
     * @internal param int $calendarRangeEnd
     * @internal param \Events $module
     */
    public function onGetAllEvents($events, $calendars): array
    {
        /** @type \Model $objPage */
        global $objPage;

        // Walk each calendar selected in module
        foreach ($calendars as $calendarId) {
            /** @type \Model $calendar */
            $calendar = CalendarModel::findById($calendarId);
            if (null === $calendar) {
                continue;
            }

            if (!$calendar->addMetamodel) {
                continue;
            }

            // Get MetaModel object
            $metaModel = $this->metaModelsFactory->getMetaModel($calendar->metamodel);
            if (null === $metaModel) {
                continue;
            }

            $items = $metaModel->findByFilter(null); //@todo a filter should be configurable in dca

            // Walk each item in MetaModel
            while ($items->next()) {
                $event   = [];
                $addTime = false;
                $start   = 0;
                $end     = 0;

                // Walk each associated attribute
                foreach ((array)deserialize($calendar->metamodelFields, true) as $attribute) {
                    $event[$attribute['calendar_field']] = $items->getItem()->get($attribute['metamodel_field']);

                    switch ($attribute['calendar_field']) {
                        case 'startDate':
                            $event[$attribute['calendar_field']] = ToolboxOfferDate::offerStart($items->getItem());
                            $start                               = $event[$attribute['calendar_field']];
                            $addTime                             = \in_array(
                                $items->getItem()->getAttribute($attribute['metamodel_field'])->get('timetype'),
                                ['datim', 'time'],
                                true
                            );
                            break;

                        case 'endDate':
                            $event[$attribute['calendar_field']] = ToolboxOfferDate::offerEnd($items->getItem());
                            $end                                 = $event[$attribute['calendar_field']];
                            break;
                    }
                }

                $key   = date('Ymd', $start);
                $date  = Date::parse($objPage->dateFormat, $start);
                $day   = $GLOBALS['TL_LANG']['DAYS'][date('w', $start)];
                $month = $GLOBALS['TL_LANG']['MONTHS'][date('n', $start) - 1];

                $span = ContaoCalendar::calculateSpan($start, $end);

                if ($span > 0) {
                    $date = Date::parse($objPage->dateFormat, $start).' – '.Date::parse($objPage->dateFormat, $end);
                    $day  = '';
                }

                $time = '';

                if ($addTime) {
                    if ($span > 0) {
                        $date = sprintf(
                            '%s – %s',
                            Date::parse($objPage->datimFormat, $start),
                            Date::parse($objPage->datimFormat, $end)
                        );
                    } elseif ($start === $end) {
                        $time = Date::parse($objPage->timeFormat, $start);
                    } else {
                        $time = sprintf(
                            '%s – %s',
                            Date::parse($objPage->timeFormat, $start),
                            Date::parse($objPage->timeFormat, $end)
                        );
                    }
                }

                // Overwrite some settings
                $event['date']     = $date;
                $event['time']     = $time;
                $event['datetime'] = $addTime ? date('Y-m-d\TH:i:sP', $start) : date('Y-m-d', $start);
                $event['day']      = $day;
                $event['month']    = $month;
                $event['calendar'] = $calendar;
                $event['link']     = $event['title'];
                $event['target']   = '';
                $event['title']    = specialchars($event['title'], true);
//				$arrEvent['href'] = $this->generateEventUrl($objEvents, $strUrl);
                $event['class'] = ('' !== $event['cssClass']) ? ' '.$event['cssClass'] : '';
//				$arrEvent['recurring'] = $recurring;
//				$arrEvent['until'] = $until;
                $event['begin']      = $start;
                $event['end']        = $end;
                $event['details']    = '';
                $event['hasDetails'] = false;
                $event['hasTeaser']  = false;

                // Add event to global array
                $events[$key][$start][] = $event;
            }
        }

        return $events;
    }
}
