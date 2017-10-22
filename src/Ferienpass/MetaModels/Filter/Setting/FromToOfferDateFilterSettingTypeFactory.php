<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\MetaModels\Filter\Setting;

use MetaModels\Filter\Setting\FromToDateFilterSettingTypeFactory;

/**
 * Class FromToOfferDateFilterSettingTypeFactory
 * @package MetaModels\Filter\Setting
 */
class FromToOfferDateFilterSettingTypeFactory extends FromToDateFilterSettingTypeFactory
{

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->addKnownAttributeType('offer_date');
    }
}