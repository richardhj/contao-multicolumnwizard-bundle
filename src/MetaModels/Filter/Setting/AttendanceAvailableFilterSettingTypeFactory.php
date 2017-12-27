<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
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
