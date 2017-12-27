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
            ->setTypeIcon('system/modules/metamodelsfilter_checkbox/html/filter_checkbox.png')
            ->setTypeClass('Richardhj\ContaoFerienpassBundle\MetaModels\Filter\Setting\AttendanceAvailable')
            ->allowAttributeTypes('numeric');
    }
}
