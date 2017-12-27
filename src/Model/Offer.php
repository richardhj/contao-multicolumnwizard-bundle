<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
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
