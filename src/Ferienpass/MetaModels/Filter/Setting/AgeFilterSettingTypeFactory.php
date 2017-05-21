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

use MetaModels\Filter\Setting\AbstractFilterSettingTypeFactory;

/**
 * Class AgeFilterSettingTypeFactory
 * @package MetaModels\Filter\Setting
 */
class AgeFilterSettingTypeFactory extends AbstractFilterSettingTypeFactory
{

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this
            ->setTypeName('age')
            ->setTypeIcon('assets/ferienpass/core/img/filter_fp_age.png')
            ->setTypeClass('Ferienpass\MetaModels\Filter\Setting\Age')
            ->allowAttributeTypes('age');
    }
}
