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
 * Class AttendanceAvailableFilterSettingTypeFactory
 * @package MetaModels\Filter\Setting
 */
class AttendanceAvailableFilterSettingTypeFactory extends AbstractFilterSettingTypeFactory
{

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this
            ->setTypeName('attendance_available')
            ->setTypeIcon('bundles/metamodelsfiltercheckbox/filter_checkbox.png')
            ->setTypeClass(AttendanceAvailable::class)
            ->allowAttributeTypes('numeric');
    }
}
