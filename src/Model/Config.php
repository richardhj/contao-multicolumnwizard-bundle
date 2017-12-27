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

use DcGeneral\Contao\Model\AbstractSingleModel;


/**
 * Class Config
 *
 * @package Richardhj\ContaoFerienpassBundle\Model
 */
class Config extends AbstractSingleModel
{

    /**
     * Table name
     *
     * @var string
     */
    protected static $table = 'tl_ferienpass_config';

    protected static $objInstance;


    /**
     * @return array
     */
    public function getRegistrationAllowedZipCodes()
    {
        return trimsplit(',', $this->getProperty('registrationAllowedZipCodes'));
    }


    /**
     * @return array
     */
    public function getRegistrationRequiredFields()
    {
        return trimsplit(',', $this->getProperty('registrationRequiredFields'));
    }

    public function getAgeCheckMethod()
    {
        return $this->getProperty('ageCheckMethod');
    }
}
