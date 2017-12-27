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

namespace Richardhj\ContaoFerienpassBundle\MetaModels\Filter\Setting;

use MetaModels\FilterFromToBundle\FilterSetting\FromToDateFilterSettingTypeFactory;


/**
 * Class FromToOfferDateFilterSettingTypeFactory
 *
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
