<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace MetaModels\Filter\Setting;

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
            ->setTypeClass('MetaModels\Filter\Setting\AttendanceAvailable')
            ->allowAttributeTypes('numeric');
    }
}
