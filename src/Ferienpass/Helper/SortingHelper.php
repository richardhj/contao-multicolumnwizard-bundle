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
use ContaoCommunityAlliance\DcGeneral\Data\ModelIdInterface;
use ContaoCommunityAlliance\DcGeneral\Data\ModelInterface;
use ContaoCommunityAlliance\DcGeneral\EnvironmentInterface;
use ContaoCommunityAlliance\DcGeneral\Factory\DcGeneralFactory;
use ContaoCommunityAlliance\Translator\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;


class SortingHelper
{

    /**
     * @var EnvironmentInterface
     */
    private $environment;


    /**
     * @var DataProviderInterface
     */
    private $dataProvider;


    public function __construct($table)
    {
        $this->createDcGeneral($table);
        $this->dataProvider = $this->environment->getDataProvider();
    }


    public function setAttendanceAfter(ModelIdInterface $model, ModelIdInterface $previousModel = null)
    {
        $model = $this->convertModelIdToModel($model);

        if (null !== $previousModel) {
            $previousModel = $this->convertModelIdToModel($previousModel);
        }

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


    /**
     * @param ModelInterface $model
     *
     * @return \ContaoCommunityAlliance\DcGeneral\Data\CollectionInterface|ModelInterface[]|\string[]
     */
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