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
            ->setTypeClass('Richardhj\ContaoFerienpassBundle\MetaModels\Filter\Setting\Age')
            ->allowAttributeTypes('age');
    }
}
