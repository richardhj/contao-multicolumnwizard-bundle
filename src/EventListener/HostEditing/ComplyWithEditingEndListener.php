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

namespace Richardhj\ContaoFerienpassBundle\EventListener\HostEditing;


use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminatorAwareTrait;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\DcGeneral\EnvironmentInterface;
use ContaoCommunityAlliance\DcGeneral\Event\ActionEvent;
use ContaoCommunityAlliance\DcGeneral\Exception\DcGeneralRuntimeException;
use MetaModels\AttributeSelectBundle\Attribute\MetaModelSelect;
use MetaModels\DcGeneral\Data\Model;
use MetaModels\IItem;

class ComplyWithEditingEndListener
{

    use RequestScopeDeterminatorAwareTrait;

    /**
     * EditHandler constructor.
     *
     * @param RequestScopeDeterminator $scopeDeterminator The request mode determinator.
     */
    public function __construct(RequestScopeDeterminator $scopeDeterminator)
    {
        $this->setScopeDeterminator($scopeDeterminator);
    }

    /**
     * Handle the event to process the action.
     *
     * @param ActionEvent $event The action event
     *
     * @return void
     *
     * @throws DcGeneralRuntimeException
     */
    public function handleEvent(ActionEvent $event)
    {
        if (!$this->scopeDeterminator->currentScopeIsFrontend()) {
            return;
        }

        // Only run when no response given yet.
        if (null !== $event->getResponse()) {
            throw new DcGeneralRuntimeException('The permission check has no impact. Denying access to everyone.');
        }

        $this->process($event->getEnvironment());
    }

    /**
     * Handle the action.
     *
     * @param EnvironmentInterface $environment The environment.
     *
     * @return void
     *
     * @throws DcGeneralRuntimeException
     */
    public function process(EnvironmentInterface $environment)
    {
        $definition      = $environment->getDataDefinition();
        $basicDefinition = $definition->getBasicDefinition();
        if ('mm_ferienpass' !== $definition->getName()) {
            return;
        }

        $dataProvider = $environment->getDataProvider();
        $modelId      = ModelId::fromSerialized($environment->getInputProvider()->getParameter('id'));
        /** @var Model $model */
        $model = $dataProvider->fetch($dataProvider->getEmptyConfig()->setId($modelId->getId()));

        if (false === self::offerIsEditableForHost($model->getItem())) {
            $basicDefinition->setEditable(false);
            $basicDefinition->setCreatable(false);
            $basicDefinition->setDeletable(false);
        }
    }


    /**
     * Check whether one offer is editable for the host by checking the edit deadline
     *
     * @param IItem $offer
     *
     * @return bool
     */
    private static function offerIsEditableForHost(IItem $offer): bool
    {
        return (time() <= self::getHostEditEnd($offer));
    }

    /**
     * Get the host edit deadline for a pacific offer
     *
     * @param IItem $offer
     *
     * @return mixed
     */
    private static function getHostEditEnd(IItem $offer)
    {
        $passRelease = $offer->get('pass_release');

        return $passRelease[MetaModelSelect::SELECT_RAW]['host_edit_end'];
    }
}
