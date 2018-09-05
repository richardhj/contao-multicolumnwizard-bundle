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

namespace Richardhj\ContaoFerienpassBundle\EventListener\ApplicationSystem;


use Richardhj\ContaoFerienpassBundle\Model\Offer as OfferModel;

/**
 * Class AbstractApplicationSystemListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\ApplicationSystem
 */
abstract class AbstractApplicationSystemListener
{

    /**
     * The offer model.
     *
     * @var OfferModel
     */
    protected $offerModel;

    /**
     * AbstractApplicationSystemListener constructor.
     *
     * @param OfferModel $offerModel The offer model.
     */
    public function __construct(OfferModel $offerModel)
    {
        $this->offerModel = $offerModel;
    }
}
