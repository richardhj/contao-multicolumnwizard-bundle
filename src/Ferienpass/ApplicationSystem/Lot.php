<?php
/**
 * E-POSTBUSINESS API integration for Contao Open Source CMS
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package E-POST
 * @author  Richard Henkenjohann <richard-epost@henkenjohann.me>
 */

namespace Ferienpass\ApplicationSystem;


use ContaoCommunityAlliance\DcGeneral\Contao\DataDefinition\Definition\Contao2BackendViewDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetOperationButtonEvent;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\View\Command;
use Ferienpass\Event\SaveAttendanceEvent;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\AttendanceStatus;
use Ferienpass\Model\Config as FerienpassConfig;
use MetaModels\BackendIntegration\ViewCombinations;
use MetaModels\DcGeneral\Data\Model;
use MetaModels\DcGeneral\Events\MetaModel\BuildMetaModelOperationsEvent;
use MetaModels\Events\MetaModelsBootEvent;
use MetaModels\MetaModelsEvents;


class Lot extends AbstractApplicationSystem
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SaveAttendanceEvent::NAME                => [
                'updateAttendanceStatus',
            ],
            GetOperationButtonEvent::NAME            => [
                'createAttendancesButtonInOfferView',
            ],
            BuildMetaModelOperationsEvent::NAME      => [
                'addAttendancesOperationToMetaModelView',
            ],
            MetaModelsEvents::SUBSYSTEM_BOOT_BACKEND => [
                'addAttendancesToMetaModelModuleTables',
            ],
        ];
    }


    /**
     * Save the "waiting" status for one attendance per default
     *
     * @param SaveAttendanceEvent $event
     */
    public function updateAttendanceStatus(SaveAttendanceEvent $event)
    {
        $attendance = $event->getAttendance();

        if (null !== $attendance->getStatus()) {
            return;
        }

        $attendance->status = AttendanceStatus::findWaiting()->id;
        $attendance->save();
    }


    /**
     * Add the Attendances table name to the MetaModel back end module tables, to make them editable
     *
     * @param MetaModelsBootEvent $event
     */
    public function addAttendancesToMetaModelModuleTables(MetaModelsBootEvent $event)
    {
        $metaModelName = FerienpassConfig::getInstance()->offer_model;
        /** @var ViewCombinations $viewCombinations */
        $viewCombinations = $event->getServiceContainer()->getService('metamodels-view-combinations');
        $inputScreen = $viewCombinations->getInputScreenDetails($metaModelName);
        \Controller::loadDataContainer($metaModelName);

        // Add table name to back end module tables
        $GLOBALS['BE_MOD'][$inputScreen->getBackendSection()]['metamodel_'.$metaModelName]['tables']
        [] = Attendance::getTable();
    }


    /**
     * Add the "edit attendances" operation to the MetaModel back end view
     *
     * @param BuildMetaModelOperationsEvent $event
     */
    public function addAttendancesOperationToMetaModelView(BuildMetaModelOperationsEvent $event)
    {
        if ($event->getMetaModel()->getTableName() != FerienpassConfig::getInstance()->offer_model) {
            return;
        }

        /** @var Contao2BackendViewDefinitionInterface $view */
        $view = $event->getContainer()->getDefinition(Contao2BackendViewDefinitionInterface::NAME);
        $collection = $view->getModelCommands();

        $command = new Command();
        $command->setName('edit_attendances');

        $parameters = $command->getParameters();
        $parameters['table'] = Attendance::getTable();

//        if (!$command->getLabel()) {
//            $command->setLabel($operationName . '.0');
//            if (isset($extraValues['label'])) {
//                $command->setLabel($extraValues['label']);
//            }
//        }
//
//        if (!$command->getDescription()) {
//            $command->setDescription($operationName . '.1');
//            if (isset($extraValues['description'])) {
//                $command->setDescription($extraValues['description']);
//            }
//        }

        $extra = $command->getExtra();
        $extra['icon'] = 'edit.gif';
        $extra['attributes'] = 'onclick="Backend.getScrollOffset();"';
        $extra['idparam'] = 'pid';

        $collection->addCommand($command);
    }


    /**
     * Remove the "edit attendances" operation for variant bases
     *
     * @param GetOperationButtonEvent $event
     */
    public function createAttendancesButtonInOfferView(GetOperationButtonEvent $event)
    {
        if ($event->getCommand()->getName() != 'edit_attendances') {
            return;
        }
        /** @var Model $model */
        $model = $event->getModel();
        $metaModel = $model->getItem()->getMetaModel();

        if ($metaModel->hasVariants() && $model->getProperty('varbase') === '1') {
            $event->setHtml('');
        }
    }
}
