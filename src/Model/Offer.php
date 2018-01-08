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


/**
 * Class Offer
 * @package Richardhj\ContaoFerienpassBundle\Model
 */
class Offer extends AbstractSimpleMetaModel
{

    /**
     * Offer constructor.
     */
    public function __construct()
    {
        parent::__construct('mm_ferienpass');
    }
}
