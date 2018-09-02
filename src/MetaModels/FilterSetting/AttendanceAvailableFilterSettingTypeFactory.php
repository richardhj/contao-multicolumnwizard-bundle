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

use Doctrine\DBAL\Connection;
use MetaModels\Filter\Setting\AbstractFilterSettingTypeFactory;

/**
 * Class AttendanceAvailableFilterSettingTypeFactory
 *
 * @package MetaModels\Filter\Setting
 */
class AttendanceAvailableFilterSettingTypeFactory extends AbstractFilterSettingTypeFactory
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * {@inheritDoc}
     */
    public function __construct(Connection $connection)
    {
        parent::__construct();

        $this->connection = $connection;

        $this
            ->setTypeName('attendance_available')
            ->setTypeIcon('bundles/metamodelsfiltercheckbox/filter_checkbox.png')
            ->setTypeClass(AttendanceAvailable::class);
    }


    /**
     * {@inheritdoc}
     */
    public function createInstance($information, $filterSettings)
    {
        return new AttendanceAvailable($filterSettings, $information, $this->connection);
    }
}
