<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table;


use Contao\Controller;
use MetaModels\ViewCombination\ViewCombination;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

/**
 * Class AbstractAddAttendancesTableToBackendModuleListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table
 */
abstract class AbstractAddAttendancesTableToBackendModuleListener
{

    /**
     * The view combination with information about the current screen.
     *
     * @var ViewCombination
     */
    private $viewCombination;

    /**
     * AbstractAddAttendancesTableToBackendModuleListener constructor.
     *
     * @param ViewCombination $viewCombination The view combination with information about the current screen.
     */
    public function __construct(ViewCombination $viewCombination)
    {
        $this->viewCombination = $viewCombination;
    }

    /**
     * @param string $moduleTable The table name.
     */
    protected function addTableForModule(string $moduleTable): void
    {
        $viewCombination = $this->viewCombination;
        $screen          = $viewCombination->getScreen($moduleTable);
        $backendSection  = $screen['meta']['backendsection'];

        Controller::loadDataContainer($moduleTable);

        // Add table name to back end module tables
        $GLOBALS['BE_MOD'][$backendSection]['metamodel_' . $moduleTable]['tables'][] = Attendance::getTable();
    }
}
