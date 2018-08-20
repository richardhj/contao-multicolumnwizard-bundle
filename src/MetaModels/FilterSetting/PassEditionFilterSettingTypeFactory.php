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

namespace Richardhj\ContaoFerienpassBundle\MetaModels\FilterSetting;

use MetaModels\Filter\Setting\AbstractFilterSettingTypeFactory;

/**
 * Class PassEditionFilterSettingTypeFactory
 *
 * @package Richardhj\ContaoFerienpassBundle\MetaModels\FilterSetting
 */
class PassEditionFilterSettingTypeFactory extends AbstractFilterSettingTypeFactory
{

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this
            ->setTypeName('pass_edition')
            ->setTypeIcon('bundles/richardhjcontaoferienpass/img/filter_fp_age.png')
            ->setTypeClass(PassEdition::class)
            ->allowAttributeTypes('select');
    }
}