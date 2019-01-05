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

namespace Richardhj\ContaoFerienpassBundle\Helper;

use Contao\System;
use Richardhj\ContaoFerienpassBundle\Model\Offer;
use MetaModels\Attribute\IAttribute;
use MetaModels\IItem;


/**
 * Class ToolboxOfferDate
 *
 * @package Richardhj\ContaoFerienpassBundle\Helper
 */
class ToolboxOfferDate
{

    /**
     * Get the offer's beginning datetime as timestamp.
     *
     * @param mixed $offer The offer id or instance.
     *
     * @return int|null The timestamp or null if no date given
     */
    public static function offerStart($offer): ?int
    {
        $offer     = self::fetchOffer($offer);
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
     * @param mixed $offer The offer id or instance.
     *
     * @return int|null The timestamp or null if no date given
     */
    public static function offerEnd($offer): ?int
    {
        $offer     = self::fetchOffer($offer);
        $attribute = self::fetchDateAttribute($offer);

        $date = $offer->get($attribute->getColName());

        if (null === $date) {
            return null;
        }

        $date = array_pop($date);

        return $date['end'];
    }


    /**
     * @param IItem $item The offer.
     *
     * @return IAttribute
     */
    public static function fetchDateAttribute(IItem $item): IAttribute
    {
        return $item->getAttribute('date_period');
    }


    /**
     * @param IItem|int $offer The offer id or instance.
     *
     * @return IItem|null
     */
    protected static function fetchOffer($offer): ?IItem
    {
        if ($offer instanceof IItem) {
            if ('mm_ferienpass' !== $offer->getMetaModel()->getTableName()) {
                throw new \LogicException('Given item MetaModel does not match');
            }

            return $offer;
        }

        /** @var Offer $offerModel */
        $offerModel = System::getContainer()->get('richardhj.ferienpass.model.offer');

        return $offerModel->findById($offer);
    }
}