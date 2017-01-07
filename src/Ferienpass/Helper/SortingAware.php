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


use Contao\Model\Event\PreSaveModelEvent;
use ContaoCommunityAlliance\DcGeneral\Controller\ModelCollector;
use ContaoCommunityAlliance\DcGeneral\Controller\SortingManager;
use ContaoCommunityAlliance\DcGeneral\Data\DataProviderInterface;
use ContaoCommunityAlliance\DcGeneral\Data\DefaultCollection;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\DcGeneral\Data\ModelIdInterface;
use ContaoCommunityAlliance\DcGeneral\Data\ModelInterface;
use ContaoCommunityAlliance\DcGeneral\EnvironmentInterface;
use ContaoCommunityAlliance\DcGeneral\Factory\DcGeneralFactory;
use ContaoCommunityAlliance\Translator\TranslatorInterface;
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


    private static $instance;


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
            PreSaveModelEvent::NAME => [
                'setSorting',
            ],
        ];
    }


    public function setSorting(PreSaveModelEvent $event)
    {
        $attendance = $event->getModel();

        if ('BE' === TL_MODE || $attendance->sorting) {
            return;
        }

        $lastAttendance = Attendance::findLastByOfferAndStatus($attendance->offer, $attendance->status);
        $sorting = (null !== $lastAttendance) ? $lastAttendance->sorting : 0;

        $data = $event->getData();
        $data['sorting'] = $sorting + 128;
        $event->setData($data);
    }


    public function setAttendanceAfter(ModelIdInterface $model, ModelIdInterface $previousModel)
    {
        $model = $this->convertModelIdToModel($model);
        $previousModel = $this->convertModelIdToModel($previousModel);

        $models = new DefaultCollection();
        $models->push($model);

        $siblings = self::findSiblings($model);

        $sortingManager = new SortingManager($models, $siblings, 'sorting', $previousModel);
        $result = $sortingManager->getResults();

        $this->dataProvider->saveEach($result);
    }


    private function convertModelIdToModel(ModelIdInterface $modelId)
    {
        $modelCollector = new ModelCollector($this->environment);

        return $modelCollector->getModel($modelId->getId(), $modelId->getDataProviderName());
    }


    public static function init($table)
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        self::$instance->createDcGeneral($table);
        self::$instance->dataProvider = self::$instance->environment->getDataProvider();

        return self::$instance;
    }


    private function findSiblings(ModelInterface $model)
    {
        $config = $this->environment->getBaseConfigRegistry()->getBaseConfig();
        $config->setSorting(['sorting' => 'ASC']);

        $filters = $config->getFilter();
        $filters[] = [
            'operation' => '=',
            'property'  => 'offer',
            'value'     => $model->getProperty('offer'),
        ];
        $filters[] = [
            'operation' => '=',
            'property'  => 'status',
            'value'     => $model->getProperty('status'),
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