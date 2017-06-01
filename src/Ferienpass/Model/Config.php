<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Model;

use DcGeneral\Contao\Model\AbstractSingleModel;


/**
 * Class Config
 *
 * @package Ferienpass\Model
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
