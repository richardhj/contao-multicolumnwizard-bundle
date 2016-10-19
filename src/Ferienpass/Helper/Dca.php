<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 * Copyright (c) 2015-2015 Richard Henkenjohann
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard-ferienpass@henkenjohann.me>
 */

namespace Ferienpass\Helper;

use Contao\Input;
use ContaoCommunityAlliance\DcGeneral\Contao\Compatibility\DcCompat;
use ContaoCommunityAlliance\DcGeneral\Contao\DataDefinition\Definition\Contao2BackendViewDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\Contao\Dca\Populator\DataProviderPopulator;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\EncodePropertyValueFromWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetOperationButtonEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ModelToLabelEvent;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\BasicDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\View\Command;
use ContaoCommunityAlliance\DcGeneral\Event\PostPersistModelEvent;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\PopulateEnvironmentEvent;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\AttendanceStatus;
use Ferienpass\Model\Config as FerienpassConfig;
use Ferienpass\Model\DataProcessing;
use Ferienpass\Model\Offer;
use MetaModels\BackendIntegration\ViewCombinations;
use MetaModels\DcGeneral\Data\Model;
use MetaModels\DcGeneral\Events\MetaModel\BuildMetaModelOperationsEvent;
use MetaModels\Events\MetaModelsBootEvent;
use MetaModels\Factory;
use MetaModels\MetaModelsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class Dca implements EventSubscriberInterface
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            GetOperationButtonEvent::NAME            => [
                'createAttendancesButtonInOfferView',
            ],
            BuildMetaModelOperationsEvent::NAME      => [
                'addAttendancesOperationToMetaModelView',
            ],
            MetaModelsEvents::SUBSYSTEM_BOOT_BACKEND => [
                'addAttendancesToMetaModelModuleTables',
            ],
//            DcGeneralEvents::ACTION                  => [
//                ['setParentedListModeWhenPidParameterPresent', 100],
//            ],
            PopulateEnvironmentEvent::NAME           => [
                ['populateEnvironmentForAttendancesChildTable', DataProviderPopulator::PRIORITY * 2],
            ],
            ModelToLabelEvent::NAME                  => [
                ['addMemberEditLinkForParticipantListView', -10],
            ],
            PostPersistModelEvent::NAME              => [
                'triggerSyncForOffer',
            ],
            EncodePropertyValueFromWidgetEvent::NAME => [
                'triggerAttendanceStatusChange',
            ],
        ];
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


    /**
     * Add the "edit attendances" operation to the MetaModel back end view
     *
     * @param BuildMetaModelOperationsEvent $event
     */
    public function addAttendancesOperationToMetaModelView(BuildMetaModelOperationsEvent $event)
    {
        if (!in_array(
            $event->getMetaModel()->getTableName(),
            [FerienpassConfig::getInstance()->offer_model, FerienpassConfig::getInstance()->participant_model]
        )
        ) {
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
     * Add the Attendances table name to the MetaModel back end module tables, to make them editable
     *
     * @param MetaModelsBootEvent $event
     */
    public function addAttendancesToMetaModelModuleTables(MetaModelsBootEvent $event)
    {
        foreach ([
                     FerienpassConfig::getInstance()->offer_model,
                     FerienpassConfig::getInstance()->participant_model,
                 ] as $metaModelName) {
            /** @var ViewCombinations $viewCombinations */
            $viewCombinations = $event->getServiceContainer()->getService('metamodels-view-combinations');
            $inputScreen = $viewCombinations->getInputScreenDetails($metaModelName);
            \Controller::loadDataContainer($metaModelName);

            // Add table name to back end module tables
            $GLOBALS['BE_MOD'][$inputScreen->getBackendSection()]['metamodel_'.$metaModelName]['tables']
            [] = Attendance::getTable();
        }
    }


    public function populateEnvironmentForAttendancesChildTable(PopulateEnvironmentEvent $event)
    {
        $environment = $event->getEnvironment();
        $definition = $environment->getDataDefinition();

        if ($definition->getName() !== Attendance::getTable()
            || null === ($pid = $environment->getInputProvider()->getParameter('pid'))
        ) {
            return;
        };

        // Set parented list mode
        $environment->getDataDefinition()->getBasicDefinition()->setMode(BasicDefinitionInterface::MODE_PARENTEDLIST);
        // Set parent data provider corresponding to pid
        $definition->getBasicDefinition()->setParentDataProvider(ModelId::fromSerialized($pid)->getDataProviderName());
    }


//    /**
//     * Set parented list mode and parent data provider, so that the attendances are editable as child modules too
//     *
//     * @param ActionEvent $event
//     */
//    public function setParentedListModeWhenPidParameterPresent(ActionEvent $event)
//    {
//        $environment = $event->getEnvironment();
//        $definition = $environment->getDataDefinition();
//
//        if ($definition->getName() !== Attendance::getTable()
//            || null === ($pid = $environment->getInputProvider()->getParameter('pid'))
//        ) {
//            return;
//        };
//
//        // Set parented list mode
//        $environment->getDataDefinition()->getBasicDefinition()->setMode(BasicDefinitionInterface::MODE_PARENTEDLIST);
//        // Set parent data provider corresponding to pid
//        $definition->getBasicDefinition()->setParentDataProvider(ModelId::fromSerialized($pid)->getDataProviderName());
//    }


    /**
     * Get MetaModel attributes grouped by MetaModel
     * @category options_callback
     * @return array
     */
    public function getMetaModelsAttributes()
    {
        $return = [];

        foreach ($this->getMetaModels() as $table => $metaModelTitle) {
            foreach (Factory::getDefaultFactory()->getMetaModel($table)->getAttributes(
            ) as $attributeName => $attribute) {
                $return[$table][$attributeName] = $attribute->getName();
            }
        }

        return $return;
    }


    /**
     * Get MetaModels
     * @category options_callback
     * @return array
     */
    public function getMetaModels()
    {
        $factory = Factory::getDefaultFactory();
        $return = [];

        foreach ($factory->collectNames() as $table) {
            $return[$table] = $factory->getMetaModel($table)->getName();
        }

        return $return;
    }


    /**
     * Check whether the type of the selected attribute fits with the one assumed
     *
     * @param mixed          $value
     * @param \DataContainer $dc
     *
     * @return mixed
     * @throws \Exception
     */
    public function checkMetaModelAttributeType($value, $dc)
    {
        $attributeType = $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['metamodel_attribute_type'];
        $metaModelTableName = FerienpassConfig::getInstance()
            ->{$GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['conditionField']};

        $metaModel = Factory::getDefaultFactory()->getMetaModel($metaModelTableName);

        if (null === $metaModel) {
            return '';
        }

        $attribute = $metaModel->getAttribute($value);

        if (null !== $attribute && $attributeType != $attribute->get('type')) {
            throw new \Exception(sprintf('Selected attribute is not type "%s"', $attributeType));
        }

        return $value;
    }


    /**
     * Get status change notifications
     * @category options_callback
     *
     * @return array
     */
    public function getNotificationChoices()
    {
        $notifications = \Database::getInstance()
            ->query("SELECT id,title FROM tl_nc_notification WHERE type='offer_al_status_change' ORDER BY title");

        return $notifications->fetchEach('title');
    }


    /**
     * Get all data processings with "sync" activated
     * @category options_callback
     *
     * @return array
     */
//	public function getDataProcessingChoices()
//	{
//		/** @var \Model\Collection $objModel */
//		$objModel = DataProcessing::findBy('sync', 1);
//
//		return (null !== $objModel) ? $objModel->fetchEach('name') : array();
//	}


    /**
     * Get documents
     * @category options_callback
     *
     * @return array
     */
    public function getDocumentChoices()
    {
        $notifications = \Database::getInstance()
            ->query("SELECT id,name FROM tl_ferienpass_document ORDER BY name");

        return $notifications->fetchEach('name');
    }


    /**
     * Get all select attributes for the owner attribute
     * @category options_callback
     *
     * @param DcCompat $dc
     *
     * @return array
     */
    public function getOwnerAttributeChoices($dc)
    {
        $attributes = \Database::getInstance()
            ->prepare(
                "SELECT id,name FROM tl_metamodel_attribute WHERE pid=? AND type='select' AND select_id='id' ORDER BY sorting"
            )
            ->execute($dc->id);

        return $attributes->fetchEach('name');
    }


    /**
     * Get all metamodel render settings
     * @category options_callback
     *
     * @param \DataContainer $dc
     *
     * @return array
     */
    public function getAllMetaModelRenderSettings($dc)
    {
        $renderSettings = \Database::getInstance()
            ->query('SELECT * FROM tl_metamodel_rendersettings');

        // Sort the render settings.
        return asort($renderSettings->fetchEach('name'));
    }


    /**
     * Get all the offer MetaModel's render settings
     * @category options_callback
     *
     * @return array
     */
    public function getOffersMetaModelRenderSettings()
    {
        $renderSettings = \Database::getInstance()
            ->prepare('SELECT * FROM tl_metamodel_rendersettings WHERE pid=? ORDER BY name')
            ->execute(Offer::getInstance()->getMetaModel()->get('id'));

        // Sort the render settings.
        return $renderSettings->fetchEach('name');
    }


    /**
     * Get all front end editable member dca fields
     * @category options_callback
     *
     * @return array
     */
    public function getEditableMemberProperties()
    {
        $return = [];

        \System::loadLanguageFile('tl_member');
        $this->loadDataContainer('tl_member');

        foreach ($GLOBALS['TL_DCA']['tl_member']['fields'] as $k => $v) {
            if ($v['eval']['feEditable']) {
                $return[$k] = $GLOBALS['TL_DCA']['tl_member']['fields'][$k]['label'][0];
            }
        }

        return $return;
    }


    /**
     * Add default attendance status options if none are set
     * @category onload_callback
     */
    public function addDefaultStatus()
    {
        if ('' !== Input::get('act') || AttendanceStatus::countAll() === count($GLOBALS['FERIENPASS_STATUS'])) {
            return;
        }

        $status = [];

        foreach ($GLOBALS['FERIENPASS_STATUS'] as $number => $statusName) {
            $status[] = [
                'type'     => $number,
                'name'     => lcfirst($statusName),
                'cssClass' => $statusName,
            ];
        }

        foreach ($status as $data) {
            if (null !== AttendanceStatus::findByType($data['type'])) {
                continue;
            }

            $objStatus = new AttendanceStatus();
            $objStatus->setRow($data);
            $objStatus->save();
        }
    }


    public function addMemberEditLinkForParticipantListView(ModelToLabelEvent $event)
    {
        $model = $event->getModel();

        if ($model instanceof Model
            && FerienpassConfig::getInstance()->participant_model === $model->getProviderName()
        ) {
            $args = $event->getArgs();

            $metaModel = $model->getItem()->getMetaModel();
            $parentColName = $metaModel->getAttributeById($metaModel->get('owner_attribute'))->getColName();

            // No parent referenced
            if (!$args[$parentColName]) {
                return;
            }

            \System::loadLanguageFile('tl_member');

            $parentRaw = $model->getItem()->get($parentColName);

            // Adjust the label
            foreach ($args as $k => $v) {
                switch ($k) {
                    case $parentColName:
                        /** @noinspection HtmlUnknownTarget */
                        $args[$k] = sprintf(
                            '<a href="contao/main.php?do=member&amp;act=edit&amp;id=%1$u&amp;popup=1&amp;nb=1&amp;rt=%4$s" class="open_parent" title="%3$s" onclick="Backend.openModalIframe({\'width\':768,\'title\':\'%3$s\',\'url\':this.href});return false">%2$s</a>',
                            // Member ID
                            $parentRaw['id'],
                            // Link
                            '<i class="fa fa-external-link tl_gray"></i> '.$args[$k],
                            // Member edit description
                            sprintf(
                                $GLOBALS['TL_LANG']['tl_member']['edit'][1],
                                $parentRaw['id']
                            ),
                            REQUEST_TOKEN
                        );
                        break;

                    default:
                        if ('' === $model->getItem()->get($k) && '' !== ($parentData = $parentRaw[$k])) {
                            $args[$k] = sprintf('<span class="tl_gray">%s</span>', $parentData);
                        }
                }
            }

            $event->setArgs($args);
        }
    }


    public function triggerSyncForOffer(PostPersistModelEvent $event)
    {
        $model = $event->getModel();

        if ($model instanceof Model
            && FerienpassConfig::getInstance()->offer_model === $model->getProviderName()
        ) {
            /** @type \Model\Collection|DataProcessing $processsings */
            $processsings = DataProcessing::findBy('sync', '1');

            while (null !== $processsings && $processsings->next()) {
                $processsings->current()->run([$model->getId()]);

                \System::log(
                    sprintf
                    (
                        'Synchronisation for offer ID %u via data processing "%s" (ID %u) was processed.',
                        $model->getId(),
                        $processsings->current()->name,
                        $processsings->current()->id
                    ),
                    __METHOD__,
                    TL_GENERAL
                );
            }
        }
    }


    public function triggerAttendanceStatusChange(EncodePropertyValueFromWidgetEvent $event)
    {
        if (($event->getEnvironment()->getDataDefinition()->getName() !== FerienpassConfig::getInstance()->offer_model)
            || ($event->getProperty() !== FerienpassConfig::getInstance()->offer_attribute_applicationlist_max)
        ) {
            return;
        }

        // Trigger attendance status update
        Attendance::updateStatusByOffer($event->getModel()->getProperty('id'));
    }
}
