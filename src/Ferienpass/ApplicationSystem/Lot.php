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
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
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


    public function updateAttendanceStatus(SaveAttendanceEvent $event)
    {
        $attendance = $event->getAttendance();

        if (null !== $attendance->getStatus()) {
            return;
        }

        $attendance->status = AttendanceStatus::findWaiting()->id;
        $attendance->save();
    }


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
