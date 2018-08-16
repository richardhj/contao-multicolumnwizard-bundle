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
use MetaModels\AttributeAliasBundle\Attribute\Alias;
use MetaModels\AttributeCombinedValuesBundle\Attribute\CombinedValues;
use MetaModels\AttributeFileBundle\Attribute\File;
use MetaModels\AttributeLongtextBundle\Attribute\Longtext;
use MetaModels\AttributeNumericBundle\Attribute\Numeric;
use MetaModels\AttributeDecimalBundle\Attribute\Decimal;
use MetaModels\AttributeTableTextBundle\Attribute\TableText;
use MetaModels\AttributeTextBundle\Attribute\Text;
use MetaModels\AttributeUrlBundle\Attribute\Url;
use Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\OfferDate\OfferDate;

class ConvertDomElementToNativeWidgetListener
{

    public function convertSimpleAttributes(ConvertDomElementToNativeWidgetEvent $event): void
    {
        $attribute = $event->getAttribute();
        if (!($attribute instanceof Alias)
            || !($attribute instanceof CombinedValues)
            || !($attribute instanceof Decimal)
            || !($attribute instanceof Longtext)
            || !($attribute instanceof Numeric)
            || !($attribute instanceof Text)
            || !($attribute instanceof Url)
            || null !== $event->getValue()
        ) {
            return;
        }

        $widget = $event->getDomElement()->nodeValue;
        $value  = $attribute->widgetToValue($widget, $event->getItem()->get('id'));
        $event->setValue($value);
    }

    public function convertOfferDateAttribute(ConvertDomElementToNativeWidgetEvent $event): void
    {
        $attribute = $event->getAttribute();
        if (!($attribute instanceof OfferDate) || null !== $event->getValue()) {
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

    public function convertTableTextAttribute(ConvertDomElementToNativeWidgetEvent $event): void
    {
        $attribute = $event->getAttribute();
        if (!($attribute instanceof TableText)
            || null !== $event->getValue()
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
            if ($c === $cc) {
                $c = 0;
                $r++;
            }

            $widget[$r]['col_'.$c] = $cell->nodeValue;

            $c++;
        }

        $value = $attribute->widgetToValue($widget, $event->getItem()->get('id'));
        $event->setValue($value);
    }

    public function convertFileAttribute(ConvertDomElementToNativeWidgetEvent $event): void
    {
        $attribute = $event->getAttribute();
        if (!($attribute instanceof File) || null !== $event->getValue()) {
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
