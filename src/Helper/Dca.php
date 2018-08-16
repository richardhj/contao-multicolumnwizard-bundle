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
            PrePersistModelEvent::NAME               => [
                ['prohibitDuplicateKeyOnSaveAttendance', -100],
                ['alterNewAttendancePrePersist'],
            ],
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
            ->execute(1);

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
}
