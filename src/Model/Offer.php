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

namespace Richardhj\ContaoFerienpassBundle\Model;

use MetaModels\Factory;


/**
 * Class Offer
 * @package Richardhj\ContaoFerienpassBundle\Model
 */
class Offer extends AbstractSimpleMetaModel
{

    /**
     * Offer constructor.
     *
     * @param Factory $factory
     */
    public function __construct(Factory $factory)
    {
        parent::__construct($factory,'mm_ferienpass');
    }
}
