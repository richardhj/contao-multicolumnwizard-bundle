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

namespace Richardhj\ContaoFerienpassBundle\Model;


/**
 * Class Offer
 * @package Richardhj\ContaoFerienpassBundle\Model
 */
class Offer extends MetaModelBridge
{

    /**
     * The object instance
     *
     * @var Offer
     */
    protected static $instance;


    /**
     * The table name
     *
     * @var string
     */
    protected static $tableName = 'mm_ferienpass';

}
