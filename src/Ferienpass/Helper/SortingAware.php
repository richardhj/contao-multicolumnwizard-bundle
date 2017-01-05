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


use ContaoCommunityAlliance\DcGeneral\Controller\ModelCollector;
use ContaoCommunityAlliance\DcGeneral\Controller\SortingManager;
use ContaoCommunityAlliance\DcGeneral\Data\DataProviderInterface;
use ContaoCommunityAlliance\DcGeneral\Data\DefaultCollection;
use ContaoCommunityAlliance\DcGeneral\EnvironmentInterface;
use ContaoCommunityAlliance\DcGeneral\Factory\DcGeneralFactory;
use ContaoCommunityAlliance\Translator\TranslatorInterface;
use Ferienpass\Event\SaveAttendanceEvent;
use Ferienpass\Model\Attendance;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class SortingAware implements EventSubscriberInterface
{

    /**
     * @var EnvironmentInterface
     */
    private $environment;


    /**
     * @var DataProviderInterface
     */
    private $dataProvider;


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
            SaveAttendanceEvent::NAME => [
                'updateSorting',
            ],
        ];
    }


    public function updateSorting(SaveAttendanceEvent $event)
    {
        if ('BE' === TL_MODE || $event->getModel()->status === $event->getOriginalModel()->status) {
            return;
        }

        $attendance = $event->getModel();
        $lastAttendance = Attendance::findLastByOfferAndStatus($attendance->offer, $attendance->status);

        $this->createDcGeneral($attendance::getTable());
        $this->dataProvider = $this->environment->getDataProvider();

        $modelCollector = new ModelCollector($this->environment);
        $model = $modelCollector->getModel($attendance->id, $attendance::getTable());

        $models = new DefaultCollection();
        $models->push($model);

        if (null !== $lastAttendance) {
            $lastAttendance = $modelCollector->getModel($lastAttendance->id, Attendance::getTable());
        }

        $siblings = $this->findSiblings($attendance);

        $sortingManager = new SortingManager($models, $siblings, 'sorting', $lastAttendance);
        $result = $sortingManager->getResults();

        $this->dataProvider->saveEach($result);
    }


    private function findSiblings(Attendance $attendance)
    {
        $config = $this->environment->getBaseConfigRegistry()->getBaseConfig();
        $config->setSorting(['sorting' => 'ASC']);

        $filters = $config->getFilter();
        $filters[] = [
            'operation' => '=',
            'property'  => 'offer',
            'value'     => $attendance->offer,
        ];
        $filters[] = [
            'operation' => '=',
            'property'  => 'status',
            'value'     => $attendance->status,
        ];
        $config->setFilter($filters);

        return $this->dataProvider->fetchAll($config);
    }


    /**
     * Create the dc-general and return it's environment instance.
     *
     * @param string $containerName The name of the data container to edit.
     *
     * @return EnvironmentInterface
     */
    private function createDcGeneral($containerName)
    {
        global $container;
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $container['event-dispatcher'];

        $factory = new DcGeneralFactory();
        $dcGeneral = $factory
            ->setContainerName($containerName)
            ->setEventDispatcher($dispatcher)
            ->setTranslator($this->getTranslator())
            ->createDcGeneral();

        $this->environment = $dcGeneral->getEnvironment();

        return $this->environment;
    }


    /**
     * Get the translator from the service container.
     *
     * @return TranslatorInterface
     *
     * @throws \RuntimeException When the DIC or translator have not been correctly initialized.
     */
    private function getTranslator()
    {
        if (!($container = $GLOBALS['container']) instanceof \Pimple) {
            throw new \RuntimeException('The dependency container has not been initialized correctly.');
        }

        $translator = $container['translator'];

        if (!$translator instanceof TranslatorInterface) {
            throw new \RuntimeException('The dependency container has not been initialized correctly.');
        }

        return $translator;
    }

}