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

namespace Richardhj\ContaoFerienpassBundle\Helper;

use Contao\BackendUser;
use Contao\ContentModel;
use Contao\Input;
use Contao\System;
use ContaoCommunityAlliance\DcGeneral\Contao\Compatibility\DcCompat;
use ContaoCommunityAlliance\DcGeneral\Contao\DataDefinition\Definition\Contao2BackendViewDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\Contao\Dca\Populator\DataProviderPopulator;
use ContaoCommunityAlliance\DcGeneral\Contao\InputProvider;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\BuildWidgetEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetOperationButtonEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ModelToLabelEvent;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\BasicDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\DefaultPropertiesDefinition;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\View\Command;
use ContaoCommunityAlliance\DcGeneral\Event\PostPersistModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PrePersistModelEvent;
use ContaoCommunityAlliance\DcGeneral\Exception\DcGeneralRuntimeException;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\CreateDcGeneralEvent;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\PopulateEnvironmentEvent;
use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;
use Richardhj\ContaoFerienpassBundle\Model\Offer;
use MetaModels\BackendIntegration\ViewCombinations;
use MetaModels\DcGeneral\Data\Model;
use MetaModels\DcGeneral\Events\MetaModel\BuildMetaModelOperationsEvent;
use MetaModels\Events\MetaModelsBootEvent;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IItem;
use MetaModels\IMetaModelsServiceContainer;
use MetaModels\Item as MetaModelsItem;
use MetaModels\MetaModelsEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class Dca
 *
 * @package Richardhj\ContaoFerienpassBundle\Helper
 */
class Dca implements EventSubscriberInterface
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            GetOperationButtonEvent::NAME            => [
                ['createAttendancesButtonInOfferView'],
            ],
            BuildMetaModelOperationsEvent::NAME      => [
                ['addAttendancesOperationToMetaModelView'],
            ],
            MetaModelsEvents::SUBSYSTEM_BOOT_BACKEND => [
                ['addAttendancesToMetaModelModuleTables'],
            ],
            PrePersistModelEvent::NAME               => [
                ['prohibitDuplicateKeyOnSaveAttendance', -100],
                ['alterNewAttendancePrePersist'],
            ],
            PopulateEnvironmentEvent::NAME           => [
                ['populateEnvironmentForAttendancesChildTable', DataProviderPopulator::PRIORITY + 100],
            ],
            ModelToLabelEvent::NAME                  => [
                ['addMemberEditLinkForParticipantListView', -10],
            ],
            PostPersistModelEvent::NAME              => [
                ['triggerSyncForOffer'],
                ['handleApplicationListMaxChange'],
                ['handlePassReleaseChanges'],
            ],
            GetPropertyOptionsEvent::NAME            => [
                ['loadDataProcessingFilterOptions'],
                ['loadDataProcessingSortAttributes'],
            ],
            CreateDcGeneralEvent::NAME               => [
                ['buildFilterParamsForDataProcessing'],
            ],
            BuildWidgetEvent::NAME                   => [
//                ['loadOfferDateWidget']
            ]
        ];
    }

    /**
     * Retrieve the event dispatcher.
     *
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->getServiceContainer()->getEventDispatcher();
    }

    /**
     * Retrieve the service container.
     *
     * @return IMetaModelsServiceContainer
     *
     * @SuppressWarnings(PHPMD.Superglobals)
     * @SuppressWarnings(PHPMD.CamelCaseVariableName)
     */
    public function getServiceContainer()
    {
        return $GLOBALS['container']['metamodels-service-container'];
    }

    /**
     * Retrieve the database instance.
     *
     * @return \Contao\Database
     */
    protected function getDatabase()
    {
        return $this->getServiceContainer()->getDatabase();
    }


    /**
     * Add the "edit attendances" operation to the MetaModel back end view
     *
     * @param BuildMetaModelOperationsEvent $event
     */
    public function addAttendancesOperationToMetaModelView(BuildMetaModelOperationsEvent $event)
    {
        if (!in_array($event->getMetaModel()->getTableName(), ['mm_ferienpass', 'mm_participant'])) {
            return;
        }

        /** @var Contao2BackendViewDefinitionInterface $view */
        $view          = $event->getContainer()->getDefinition(Contao2BackendViewDefinitionInterface::NAME);
        $collection    = $view->getModelCommands();
        $operationName = 'edit_attendances';

        $command = new Command();
        $command->setName($operationName);

        $parameters          = $command->getParameters();
        $parameters['table'] = Attendance::getTable();

        if (!$command->getLabel()) {
            $command->setLabel($operationName . '.0');
            if (isset($extraValues['label'])) {
                $command->setLabel($extraValues['label']);
            }
        }
        if (!$command->getDescription()) {
            $command->setDescription($operationName . '.1');
            if (isset($extraValues['description'])) {
                $command->setDescription($extraValues['description']);
            }
        }

        $extra               = $command->getExtra();
        $extra['icon']       = 'assets/ferienpass/core/img/users.png';
        $extra['attributes'] = 'onclick="Backend.getScrollOffset();"';
        $extra['idparam']    = 'pid';

        $collection->addCommand($command);
    }


    /**
     * Remove the "edit attendances" operation for variant bases
     *
     * @param GetOperationButtonEvent $event
     */
    public function createAttendancesButtonInOfferView(GetOperationButtonEvent $event)
    {
        /** @var Model $model */
        $model = $event->getModel();

        if ('edit_attendances' !== $event->getCommand()->getName()
            || 'mm_ferienpass' !== $model->getProviderName()
        ) {
            return;
        }

        $item = $model->getItem();

        if (!$item instanceof MetaModelsItem) {
            return;
        }

        // Disable action for variant bases
        if ($item->isVariantBase() && 0 !== $item->getVariants(null)->getCount()) {
            $event->setDisabled(true);
        }

        if (!$item->get('applicationlist_active')) {
            // Does not use the application system
            $event->setDisabled(true);
        } elseif (0 === Attendance::countByOffer($item->get('id'))) {
            // No attendances at all
            $event->setAttributes(
                sprintf('%s data-applicationlist-state="no-attendances"', $event->getAttributes())
            );
        } elseif (0 === Attendance::countByOfferAndStatus($item->get('id'), AttendanceStatus::findWaiting()->id)
        ) {
            // No attendances with `waiting` status
            $event->setAttributes(
                sprintf('%s data-applicationlist-state="all-assigned"', $event->getAttributes())
            );
        } else {
            // Needs further assignments
            $event->setAttributes(
                sprintf('%s data-applicationlist-state="needs-reassignments"', $event->getAttributes())
            );
        }
    }


    /**
     * Add the Attendances table name to the MetaModel back end module tables, to make them editable
     *
     * @param MetaModelsBootEvent $event
     */
    public function addAttendancesToMetaModelModuleTables(MetaModelsBootEvent $event)
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


    /**
     * Make the "attendances" table editable as a child table of the offer or participant
     *
     * @param PopulateEnvironmentEvent $event
     */
    public function populateEnvironmentForAttendancesChildTable(PopulateEnvironmentEvent $event)
    {
        $environment   = $event->getEnvironment();
        $definition    = $environment->getDataDefinition();
        $inputProvider = $environment->getInputProvider() ?: new InputProvider(); // FIXME Why is inputProvider null?

        if ($definition->getName() !== Attendance::getTable()
            || null === ($pid = $inputProvider->getParameter('pid'))
        ) {
            return;
        };

        $modelId = ModelId::fromSerialized($pid);

        // Set parented list mode
        $environment->getDataDefinition()->getBasicDefinition()->setMode(BasicDefinitionInterface::MODE_PARENTEDLIST);
        // Set parent data provider corresponding to pid
        $definition->getBasicDefinition()->setParentDataProvider($modelId->getDataProviderName());

        // Remove redundant legend (offer_legend in offer view)
        $palette = $definition->getPalettesDefinition()->getPaletteByName('default');

        switch ($modelId->getDataProviderName()) {
            case 'mm_ferienpass':
                $palette->removeLegend($palette->getLegend('offer'));
                break;

            case 'mm_participant':
                $palette->removeLegend($palette->getLegend('participant'));
                break;
        }
    }


    /**
     * Prohibit a "duplicate key" sql error when trying to save an attendance that is already existent
     *
     * @param PrePersistModelEvent $event
     */
    public function prohibitDuplicateKeyOnSaveAttendance(PrePersistModelEvent $event)
    {
        $environment = $event->getEnvironment();
        $definition  = $environment->getDataDefinition();

        // Not attendances table
        if ($definition->getName() !== Attendance::getTable()) {
            return;
        };

        $model = $event->getModel();

        if (!Attendance::isNotExistent($model->getProperty('participant'), $model->getProperty('offer'))) {
            \Message::addError('Es besteht schon eine Anmeldung für diesen Benutzer und dieses Angebot');
            \Controller::reload();
        }
    }


    /**
     * Set required properties for a new attendance
     *
     * @param PrePersistModelEvent $event
     */
    public function alterNewAttendancePrePersist(PrePersistModelEvent $event)
    {
        if (Attendance::getTable() !== $event->getEnvironment()->getDataDefinition()->getName()) {
            return;
        }

        $model = $event->getModel();

        // Set created timestamp
        if (null === $model->getProperty('created')) {
            $model->setProperty('created', time());
        }
    }


    /**
     * Get MetaModel attributes grouped by MetaModel
     *
     * @category options_callback
     *
     * @return array
     */
    public function getMetaModelsAttributes()
    {
        global $container;

        $return = [];

        /** @var IMetaModelsServiceContainer $serviceContainer */
        $serviceContainer = $container['metamodels-service-container'];

        foreach ($this->getMetaModels() as $table => $metaModelTitle) {
            foreach ($serviceContainer->getFactory()->getMetaModel($table)->getAttributes() as $attrName => $attribute)
            {
                $return[$table][$attrName] = $attribute->getName();
            }
        }

        return $return;
    }


    /**
     * Get MetaModels
     *
     * @category options_callback
     *
     * @return array
     */
    public function getMetaModels()
    {
        global $container;

        /** @var IMetaModelsServiceContainer $serviceContainer */
        $serviceContainer = $container['metamodels-service-container'];

        $return = [];

        foreach ($serviceContainer->getFactory()->collectNames() as $table) {
            $return[$table] = $serviceContainer->getFactory()->getMetaModel($table)->getName();
        }

        return $return;
    }


    /**
     * Get status change notifications
     *
     * @category options_callback
     *
     * @return array
     */
    public function getNotificationChoices()
    {
        $notifications = \Database::getInstance()
            ->query(
                "SELECT id,title FROM tl_nc_notification WHERE type='application_list_status_change' ORDER BY title"
            );

        return $notifications->fetchEach('title');
    }


    /**
     * Get documents
     *
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
     *
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
     *
     * @category options_callback
     *
     * @return array
     *
     * @internal param \DataContainer $dc
     */
    public function getAllMetaModelRenderSettings()
    {
        $renderSettings = \Database::getInstance()
            ->query('SELECT * FROM tl_metamodel_rendersettings');

        // Sort the render settings.
        return asort($renderSettings->fetchEach('name'));
    }


    /**
     * Get all the offer MetaModel's render settings
     *
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
     * Add default attendance status options if none are set
     *
     * @category onload_callback
     */
    public function addDefaultStatus()
    {
        $attendanceStatus = System::getContainer()->getParameter('richardhj.ferienpass.attendance_status');

        if ('' !== Input::get('act') || AttendanceStatus::countAll() === count($attendanceStatus)) {
            return;
        }

        $status = [];

        foreach ($attendanceStatus as $number => $statusName) {
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


    /**
     * Add default application systems if none are set
     *
     * @category onload_callback
     */
    public function addDefaultApplicationSystems()
    {
        $systems = ApplicationSystem::getApplicationSystemNames();

        if (null !== \Input::get('act') || ApplicationSystem::countAll() === count($systems)) {
            return;
        }

        $rows = [];

        foreach ($systems as $system) {
            $rows[] = [
                'type'  => $system,
                'title' => lcfirst($system),
            ];
        }

        foreach ($rows as $row) {
            if (null !== ApplicationSystem::findByType($row['type'])) {
                continue;
            }

            $model = new ApplicationSystem();
            $model->setRow($row);
            $model->save();
        }
    }


    /**
     * Add the member edit link when in participant list view
     *
     * @param ModelToLabelEvent $event
     */
    public function addMemberEditLinkForParticipantListView(ModelToLabelEvent $event)
    {
        $model = $event->getModel();

        if ($model instanceof Model && 'mm_participant' === $model->getProviderName()) {
            $args = $event->getArgs();

            $metaModel     = $model->getItem()->getMetaModel();
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
                            '<i class="fa fa-external-link tl_gray"></i> ' . $args[$k],
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


    /**
     * Trigger data processing when saving an offer
     *
     * @param PostPersistModelEvent $event
     */
    public function triggerSyncForOffer(PostPersistModelEvent $event)
    {
        $model = $event->getModel();

        if (!$model instanceof Model
            || 'mm_ferienpass' !== $model->getProviderName()
        ) {
            return;
        }

        /** @type \Model\Collection|DataProcessing $processing */
        $processing = DataProcessing::findBy('sync', '1');

        while (null !== $processing && $processing->next()) {
            if (!$processing->xml_single_file) {

                $variants = $model->getItem()->getVariants(null);

                $ids = [];
                if (null !== $variants) {
                    $ids = array_map(
                        function ($item) {
                            /** @var IItem $item */
                            return $item->get('id');
                        },
                        iterator_to_array($variants)
                    );
                }

                $ids = array_merge([$model->getId()], $ids);

                // FIXME getting troubles when using single_xml_file
                $filterRule = new StaticIdList($ids);
                $processing->current()
                    ->getFilter()
                    ->addFilterRule($filterRule);
            }

            $processing->current()->run();
        }
    }


    /**
     * Update the attendances when changing the "applicationlist_max" value
     *
     * @param PostPersistModelEvent $event
     */
    public function handleApplicationListMaxChange(PostPersistModelEvent $event)
    {
        $model         = $event->getModel();
        $originalModel = $event->getOriginalModel();
        if (!$model instanceof Model
            || 'mm_ferienpass' !== $event->getModel()->getProviderName()
            || $model->getProperty('applicationlist_max') === $originalModel->getProperty('applicationlist_max')
        ) {
            return;
        }

        $ids = [$model->getId()];
        if ($model->getItem()->isVariantBase()) {
            $variants = $model->getItem()->getVariants(null);
            $ids      = array_merge(
                array_map(
                    function (IItem $item) {
                        return $item->get('id');
                    },
                    iterator_to_array($variants)
                ),
                $ids
            );
        }

        foreach ($ids as $id) {
            Attendance::updateStatusByOffer($id);
        }
    }


    public function loadDataProcessingFilterOptions(GetPropertyOptionsEvent $event)
    {
        if (('tl_ferienpass_dataprocessing' !== $event->getModel()->getProviderName())
            || ('metamodel_filtering' !== $event->getPropertyName())
        ) {
            return;
        }

        $filters = \Database::getInstance()
            ->prepare('SELECT id,name FROM tl_metamodel_filter WHERE pid=?')
            ->execute(Offer::getInstance()->getMetaModel()->get('id'));

        $event->setOptions($filters->fetchEach('name'));
    }

    public function loadDataProcessingSortAttributes(GetPropertyOptionsEvent $event)
    {
        if (('tl_ferienpass_dataprocessing' !== $event->getModel()->getProviderName())
            || ('metamodel_sortby' !== $event->getPropertyName())
        ) {
            return;
        }

        $options    = [];
        $attributes = \Database::getInstance()
            ->prepare('SELECT colName,name FROM tl_metamodel_attribute WHERE pid=?')
            ->execute(Offer::getInstance()->getMetaModel()->get('id'));

        while ($attributes->next()) {
            $options[$attributes->colName] = $attributes->name;
        }

        $event->setOptions($options);
    }


    public function buildFilterParamsForDataProcessing(CreateDcGeneralEvent $event)
    {
        if ('tl_ferienpass_dataprocessing' !== ($table =
                $event->getDcGeneral()->getEnvironment()->getDataDefinition()->getName())
        ) {
            return;
        }

        // Todo We need another event which provides the model instance so we don't need to fetch the model id from the url
        try {
            $modelId = ModelId::fromSerialized(\Input::get('id'));
        } catch (DcGeneralRuntimeException $e) {
            return;
        }

        $container = $this->getServiceContainer();
        $element   = DataProcessing::findByPk($modelId->getId());

        /** @var DefaultPropertiesDefinition $propertiesDefinition */
        $propertiesDefinition = $event
            ->getDcGeneral()
            ->getEnvironment()
            ->getDataDefinition()
            ->getDefinition('properties');

        if (!$element->metamodel_filtering) {
//            $propertiesDefinition->removeProperty('metamodel_filterparams');
            return;
        }

        $filterSettings = $container
            ->getFilterFactory()
            ->createCollection($element->metamodel_filtering);

        $property = $propertiesDefinition->getProperty('metamodel_filterparams');
        $extra    = $property->getExtra();

        $extra['subfields'] = $filterSettings->getParameterDCA();
        $property->setExtra($extra);
    }


    public function handlePassReleaseChanges(PostPersistModelEvent $event)
    {
        $model = $event->getModel();

        if (!$model instanceof Model
            || 'mm_ferienpass_release' !== $model->getProviderName()
        ) {
            return;
        }

        // Update the MetaModel list by setting the correct pass release filter param
        if ($model->getProperty('show_current') !== $event->getOriginalModel()->getProperty('show_current')
            && $model->getProperty('show_current')
        ) {
            /** @var ContentModel|\Model $listElement */
            $listElements = ContentModel::findBy(['type=?', 'is_offer_list=1'], ['metamodel_content']);
            $filterParams = deserialize($listElement->metamodel_filterparams);

            $filterParams['pass_release']['value'] = $model->getId();
            while ($listElements->next()) {
                $listElements->metamodel_filterparams = serialize($filterParams);
                $listElements->save();
            }

            //TODO #3 make subdca field readonly
        }

        //todo change is_edit_list

        // Switch edit_current and edit_previous
        if ($model->getProperty('edit_current') !== $event->getOriginalModel()->getProperty('edit_current')
            && $model->getProperty('edit_current')
        ) {
            $model
                ->getItem()
                ->getMetaModel()
                ->getServiceContainer()
                ->getDatabase()
                ->prepare(
                    "UPDATE mm_ferienpass_release SET edit_current=0, edit_previous=1 WHERE edit_current=1 AND id=?"
                )
                ->execute($model->getId());
        }
    }


    public function loadOfferDateWidget(BuildWidgetEvent $event)
    {
        global $container;

        /** @var BackendUser $user */
        $user = $container['user'];

        if (!$user->offer_date_picker || 'offer_date' !== $event->getProperty()->getWidgetType()) {
            return;
        }

        // TODO Add jQuery period picker assets
    }
}
