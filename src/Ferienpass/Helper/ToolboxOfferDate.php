<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Helper;

use Ferienpass\Model\Config;
use Ferienpass\Model\Offer;
use MetaModels\Attribute\IAttribute;
use MetaModels\IItem;


/**
 * Class ToolboxOfferDate
 * @package Ferienpass\Helper
 */
class ToolboxOfferDate
{

    /**
     * Get the offer's beginning datetime as timestamp
     *
     * @param mixed $offer
     *
     * @return int|null The timestamp or null if no date given
     */
    public static function offerStart($offer)
    {
        $offer = self::fetchOffer($offer);
        $attribute = self::fetchDateAttribute($offer);

        $date = $offer->get($attribute->getColName());

        if (null === $date) {
            return null;
        }

        $date = array_shift($date);

        return $date['start'];
    }


    /**
     * Get the offer's ending datetime as timestamp
     *
     * @param mixed $offer
     *
     * @return int|null The timestamp or null if no date given
     */
    public static function offerEnd($offer)
    {
        $offer = self::fetchOffer($offer);
        $attribute = self::fetchDateAttribute($offer);

        $date = $offer->get($attribute->getColName());

        if (null === $date) {
            return null;
        }

        $date = array_pop($date);

        return $date['end'];
    }


    /**
     * @param IItem|int $offer
     *
     * @return IItem|null
     */
    protected static function fetchOffer($offer)
    {
        if ($offer instanceof IItem) {
            if ($offer->getMetaModel()->getTableName() !== Config::getInstance()->offer_model) {
                throw new \LogicException('Given item MetaModel does not match');
            }

            return $offer;
        }

        return $offer = Offer::getInstance()->findById($offer);
    }


    /**
     * @param IItem $item
     *
     * @return IAttribute
     */
    protected static function fetchDateAttribute(IItem $item)
    {
        return $item->getAttribute(Config::getInstance()->offer_attribute_date);
    }
}
