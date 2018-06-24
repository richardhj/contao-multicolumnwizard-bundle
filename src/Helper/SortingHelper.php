<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\Helper;

use ContaoCommunityAlliance\DcGeneral\Controller\ModelCollector;
use ContaoCommunityAlliance\DcGeneral\Controller\SortingManager;
use ContaoCommunityAlliance\DcGeneral\Data\CollectionInterface;
use ContaoCommunityAlliance\DcGeneral\Data\DataProviderInterface;
use ContaoCommunityAlliance\DcGeneral\Data\DefaultCollection;
use ContaoCommunityAlliance\DcGeneral\Data\ModelIdInterface;
use ContaoCommunityAlliance\DcGeneral\Data\ModelInterface;
use ContaoCommunityAlliance\DcGeneral\EnvironmentInterface;
use ContaoCommunityAlliance\DcGeneral\Factory\DcGeneralFactory;
use ContaoCommunityAlliance\Translator\TranslatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


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

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * SortingHelper constructor.
     *
     * @param                          $table
     * @param EventDispatcherInterface $dispatcher
     * @param TranslatorInterface      $translator
     */
    public function __construct($table, EventDispatcherInterface $dispatcher, TranslatorInterface $translator)
    {
        $this->createDcGeneral($table);

        $this->dataProvider = $this->environment->getDataProvider();
        $this->dispatcher   = $dispatcher;
        $this->translator   = $translator;
    }

    /**
     * Update the sorting to set a given model after a given model or at the top if none given
     *
     * @param ModelIdInterface      $modelId
     * @param ModelIdInterface|null $previousModelId
     */
    public function setAttendanceAfter(ModelIdInterface $modelId, ModelIdInterface $previousModelId = null): void
    {
        $model = $this->convertModelIdToModel($modelId);

        if (null !== $previousModelId) {
            $previousModel = $this->convertModelIdToModel($previousModelId);
        }

        $models = new DefaultCollection();
        $models->push($model);

        $siblings = $this->findSiblings($model);

        $sortingManager = new SortingManager($models, $siblings, 'sorting', $previousModel ?? null);
        $result         = $sortingManager->getResults();

        $this->dataProvider->saveEach($result);
    }

    /**
     * Convert a ModelId to a DC General conform model instance
     *
     * @param ModelIdInterface $modelId
     *
     * @return ModelInterface
     */
    private function convertModelIdToModel(ModelIdInterface $modelId): ModelInterface
    {
        $modelCollector = new ModelCollector($this->environment);

        return $modelCollector->getModel($modelId->getId(), $modelId->getDataProviderName());
    }

    /**
     * @param ModelInterface $model
     *
     * @return CollectionInterface|ModelInterface[]|string[]
     */
    private function findSiblings(ModelInterface $model)
    {
        $config = $this->environment->getBaseConfigRegistry()->getBaseConfig();
        $config->setSorting(['sorting' => 'ASC']);

        $filters   = $config->getFilter();
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
    private function createDcGeneral($containerName): EnvironmentInterface
    {
        $factory = new DcGeneralFactory();

        $dcGeneral = $factory
            ->setContainerName($containerName)
            ->setEventDispatcher($this->dispatcher)
            ->setTranslator($this->translator)
            ->createDcGeneral();

        $this->environment = $dcGeneral->getEnvironment();

        return $this->environment;
    }

}