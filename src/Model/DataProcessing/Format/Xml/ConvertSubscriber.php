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

namespace Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format\Xml;


use Contao\FilesModel;
use Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\OfferDate\OfferDate;
use MetaModels\Attribute\Alias\Alias;
use MetaModels\Attribute\CombinedValues\CombinedValues;
use MetaModels\Attribute\Decimal\Decimal;
use MetaModels\Attribute\File\File;
use MetaModels\Attribute\Longtext\Longtext;
use MetaModels\Attribute\Numeric\AttributeNumeric;
use MetaModels\Attribute\TableText\TableText;
use MetaModels\Attribute\Text\Text;
use MetaModels\Attribute\Url\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConvertSubscriber implements EventSubscriberInterface
{

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            ConvertDomElementToNativeWidgetEvent::NAME => [
                ['convertSimpleAttributes', -10],
                ['convertOfferDateAttribute'],
                ['convertTableTextAttribute'],
                ['convertFileAttribute']
            ],
        ];
    }

    public function convertSimpleAttributes(ConvertDomElementToNativeWidgetEvent $event)
    {
        $attribute = $event->getAttribute();
        if (null !== $event->getValue()
            || !($attribute instanceof Alias)
            || !($attribute instanceof CombinedValues)
            || !($attribute instanceof Decimal)
            || !($attribute instanceof Longtext)
            || !($attribute instanceof AttributeNumeric)
            || !($attribute instanceof Text)
            || !($attribute instanceof Url)
        ) {
            return;
        }

        $widget = $event->getDomElement()->nodeValue;
        $value  = $attribute->widgetToValue($widget, $event->getItem()->get('id'));
        $event->setValue($value);
    }

    public function convertOfferDateAttribute(ConvertDomElementToNativeWidgetEvent $event)
    {
        $attribute = $event->getAttribute();
        if (null !== $event->getValue()
            || !($attribute instanceof OfferDate)
        ) {
            return;
        }

        //TODO
        return;

        /** @var \DOMElement $period */
        foreach ($event->getDomElement()->getElementsByTagName('_period') as $i => $period) {
            $dates = $period->getElementsByTagName('_date');
            if (1 !== $dates->length) {
                return;
            }

            $widget[$i]['start'] = $dates->item(0)->nodeValue;
            $widget[$i]['end']   = $dates->item(1)->nodeValue;
        }

        $value = $attribute->widgetToValue($widget, $event->getItem()->get('id'));
        $event->setValue($value);
    }

    public function convertTableTextAttribute(ConvertDomElementToNativeWidgetEvent $event)
    {
        $attribute = $event->getAttribute();
        if (null !== $event->getValue()
            || !($attribute instanceof TableText)
        ) {
            return;
        }

        $widget = [];

        /** @type \DOMElement $element */
        $element = $event->getDomElement()
            ->getElementsByTagName('Tabletext')
            ->item(0);

        $cc = $element->getAttribute('aid:tcols');
        $r  = 0;
        $c  = 0;

        /** @type \DOMElement $cell */
        foreach ($element->getElementsByTagName('Cell') as $cell) {
            if ($c == $cc) {
                $c = 0;
                $r++;
            }

            $widget[$r]['col_' . $c] = $cell->nodeValue;

            $c++;
        }

        $value = $attribute->widgetToValue($widget, $event->getItem()->get('id'));
        $event->setValue($value);
    }

    public function convertFileAttribute(ConvertDomElementToNativeWidgetEvent $event)
    {
        $attribute = $event->getAttribute();
        if (null !== $event->getValue()
            || !($attribute instanceof File)
        ) {
            return;
        }

        $widget = [];

        /** @type \DOMElement $fileDom */
        foreach ($event->getDomElement()->getElementsByTagName('Link') as $fileDom) {
            // Replace remote path with local path
            $path = preg_replace(
                '/^file:\/\/[\.\/]*/',
                '',
                $fileDom->getAttribute('href')
            );

            $file = FilesModel::findByPath(urldecode($path));
            if (null === $file) {
                return;
            }

            $widget[] = $file->uuid;
        }

        $value = $attribute->widgetToValue($widget, $event->getItem()->get('id'));
        $event->setValue($value);
    }
}
