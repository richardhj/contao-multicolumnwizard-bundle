<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Helper;

use Contao\ContentModel;
use Contao\Input;
use ContaoCommunityAlliance\DcGeneral\Contao\Compatibility\DcCompat;
use ContaoCommunityAlliance\DcGeneral\Contao\DataDefinition\Definition\Contao2BackendViewDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\Contao\Dca\Populator\DataProviderPopulator;
use ContaoCommunityAlliance\DcGeneral\Contao\InputProvider;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\EncodePropertyValueFromWidgetEvent;
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
use Ferienpass\Model\ApplicationSystem;
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
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IItem;
use MetaModels\IMetaModelsServiceContainer;
use MetaModels\MetaModelsEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class Dca
 *
 * @package Ferienpass\Helper
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
                'createAttendancesButtonInOfferView',
            ],
            BuildMetaModelOperationsEvent::NAME      => [
                'addAttendancesOperationToMetaModelView',
            ],
            MetaModelsEvents::SUBSYSTEM_BOOT_BACKEND => [
                'addAttendancesToMetaModelModuleTables',
            ],
            PrePersistModelEvent::NAME               => [
                ['prohibitDuplicateKeyOnSaveAttendance', -100],
            ],
            PopulateEnvironmentEvent::NAME           => [
                ['populateEnvironmentForAttendancesChildTable', DataProviderPopulator::PRIORITY + 100],
            ],
            ModelToLabelEvent::NAME                  => [
                ['addMemberEditLinkForParticipantListView', -10],
            ],
            PostPersistModelEvent::NAME              => [
                'triggerSyncForOffer',
                'handlePassReleaseChanges'
            ],
            EncodePropertyValueFromWidgetEvent::NAME => [
                'triggerAttendanceStatusChange',
            ],
            GetPropertyOptionsEvent::NAME            => [
                ['loadDataProcessingFilterOptions'],
                ['loadDataProcessingSortAttributes']
            ],
            CreateDcGeneralEvent::NAME               => [
                'buildFilterParamsForDataProcessing'
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
     * Remove the "edit attendances" operation for variant bases
     *
     * @param GetOperationButtonEvent $event
     */
    public function createAttendancesButtonInOfferView(GetOperationButtonEvent $event)
    {
        if ('edit_attendances' !== $event->getCommand()->getName()) {
            return;
        }

        /** @var Model $model */
        $model         = $event->getModel();
        $metaModelItem = $model->getItem();

        if ($metaModelItem->isVariantBase() && 0 !== $metaModelItem->getVariants(null)->getCount()) {
            $event->setDisabled(true);
        } elseif (0 === Attendance::countByOfferAndStatus(
                $model->getProperty('id'),
                AttendanceStatus::findWaiting()->id
            )
        ) {
            //todo
//            $event->setHtml('<p>test</p>'.$event->getHtml());
        }
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
        $view       = $event->getContainer()->getDefinition(Contao2BackendViewDefinitionInterface::NAME);
        $collection = $view->getModelCommands();

        $command = new Command();
        $command->setName('edit_attendances');

        $parameters          = $command->getParameters();
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

        $extra               = $command->getExtra();
        $extra['icon']       = 'edit.gif';
        $extra['attributes'] = 'onclick="Backend.getScrollOffset();"';
        $extra['idparam']    = 'pid';

        $collection->addCommand($command);
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
     * Get MetaModel attributes grouped by MetaModel
     *
     * @category options_callback
     *
     * @return array
     */
    public function getMetaModelsAttributes()
    {
        $return = [];

        foreach ($this->getMetaModels() as $table => $metaModelTitle) {
            foreach (Factory::getDefaultFactory()->getMetaModel($table)->getAttributes() as $attrName => $attribute) {
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
        $factory = Factory::getDefaultFactory();
        $return  = [];

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
        $attributeType      = $GLOBALS['TL_DCA'][$dc->table]['fields'][$dc->field]['eval']['metamodel_attribute_type'];
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
     *
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
     * Get all front end editable member dca fields
     *
     * @category options_callback
     *
     * @return array
     */
    public function getEditableMemberProperties()
    {
        $return = [];

        \System::loadLanguageFile('tl_member');
        \Controller::loadDataContainer('tl_member');

        foreach ($GLOBALS['TL_DCA']['tl_member']['fields'] as $k => $v) {
            if ($v['eval']['feEditable']) {
                $return[$k] = $GLOBALS['TL_DCA']['tl_member']['fields'][$k]['label'][0];
            }
        }

        return $return;
    }


    /**
     * Add default attendance status options if none are set
     *
     * @category onload_callback
     */
    public function addDefaultStatus()
    {
        global $container;

        if ('' !== Input::get('act')
            || AttendanceStatus::countAll() === count(
                $container['ferienpass.attendance-status']
            )
        ) {
            return;
        }

        $status = [];

        foreach ($container['ferienpass.attendance-status'] as $number => $statusName) {
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

                $ids = array_map(
                    function ($item) {
                        /** @var IItem $item */
                        return $item->get('id');
                    },
                    $model->getItem()->getVariants(null)
                );
                $ids = array_merge([$model->getId()], $ids);

                $filterRule = new StaticIdList($ids);
                $processing->current()
                    ->getFilter()
                    ->addFilterRule($filterRule);
            }

            $processing->current()->run();
        }
    }


    /**
     * Update the attendances when changing the "applicationlist max" value
     *
     * @param EncodePropertyValueFromWidgetEvent $event
     */
    public function triggerAttendanceStatusChange(EncodePropertyValueFromWidgetEvent $event)
    {
        if (('mm_ferienpass' !== $event->getEnvironment()->getDataDefinition()->getName())
            || ('applicationlist_max' !== $event->getProperty())
        ) {
            return;
        }
//fixme
        // Trigger attendance status update
//        Attendance::updateStatusByOffer($event->getModel()->getProperty('id'));
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
            $propertiesDefinition->removeProperty('metamodel_filterparams');
            return;
        }

        $filterSettings = $container
            ->getFilterFactory()
            ->createCollection($element->metamodel_filtering);

        $property           = $propertiesDefinition->getProperty('metamodel_filterparams');
        $extra              = $property->getExtra();
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
            $listElement  = ContentModel::findById(20); // TODO hardcoded
            $filterParams = deserialize($listElement->metamodel_filterparams);

            $filterParams['pass_release']['value'] = $model->getId();
            $listElement->metamodel_filterparams   = serialize($filterParams);
            $listElement->save();

            //TODO make subdca field readonly
        }

        // Switch edit_current and edit_previous
        if ($model->getProperty('edit_current') !== $event->getOriginalModel()->getProperty('edit_current')
            && $model->getProperty('edit_current')
        ) {
            $filterRule  = new SimpleQuery(
                'SELECT id FROM mm_ferienpass_relase WHERE edit_current=1 AND id<>?',
                [$model->getId()]
            );
            $filter      = $model->getItem()->getMetaModel()->getEmptyFilter()->addFilterRule($filterRule);
            $passRelease = $model->getItem()->getMetaModel()->findByFilter($filter);

            while ($passRelease->next()) {
                $passRelease->getItem()->set('edit_current', '0');
                $passRelease->getItem()->set('edit_previous', '1');
                $passRelease->getItem()->save();
            }
        }
    }
}
