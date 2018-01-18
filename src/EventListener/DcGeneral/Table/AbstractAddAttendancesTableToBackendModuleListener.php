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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table;


use MetaModels\Events\MetaModelsBootEvent;

abstract class AbstractAddAttendancesTableToBackendModuleListener
{

    /**
     * Add the Attendances table name to the MetaModel back end module tables, to make them editable
     *
     * @param MetaModelsBootEvent $event
     */
    public function handle(MetaModelsBootEvent $event)
    {
        foreach (['mm_ferienpass', 'mm_participant'] as $metaModelName) {
            try {
                /** @var ViewCombinations $viewCombinations */
                $viewCombinations = $event->getServiceContainer()->getService('metamodels-view-combinations');
                $inputScreen      = $viewCombinations->getInputScreenDetails($metaModelName);
                $backendSection   = $inputScreen->getBackendSection();
                \Controller::loadDataContainer($metaModelName);

                // Add table name to back end module tables
                $GLOBALS['BE_MOD'][$backendSection]['metamodel_' . $metaModelName]['tables'][] = Attendance::getTable();

            } catch (\RuntimeException $e) {
                \System::log($e->getMessage(), __METHOD__, TL_ERROR);
            }
        }
    }
}