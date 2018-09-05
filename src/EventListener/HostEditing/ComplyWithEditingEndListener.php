<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\EventListener\HostEditing;


use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminatorAwareTrait;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\DcGeneral\EnvironmentInterface;
use ContaoCommunityAlliance\DcGeneral\Event\ActionEvent;
use ContaoCommunityAlliance\DcGeneral\Exception\DcGeneralRuntimeException;
use MetaModels\DcGeneral\Data\Model;
use MetaModels\IItem;
use Richardhj\ContaoFerienpassBundle\Entity\PassEdition;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Class ComplyWithEditingEndListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\HostEditing
 */
class ComplyWithEditingEndListener
{

    use RequestScopeDeterminatorAwareTrait;

    /**
     * Doctrine.
     *
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * EditHandler constructor.
     *
     * @param RequestScopeDeterminator $scopeDeterminator The request mode determinator.
     * @param ManagerRegistry          $doctrine          Doctrine.
     */
    public function __construct(RequestScopeDeterminator $scopeDeterminator, ManagerRegistry $doctrine)
    {
        $this->setScopeDeterminator($scopeDeterminator);

        $this->doctrine = $doctrine;
    }

    /**
     * Handle the event to process the action.
     *
     * @param ActionEvent $event The action event.
     *
     * @return void
     *
     * @throws DcGeneralRuntimeException
     */
    public function handleEvent(ActionEvent $event): void
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
    public function process(EnvironmentInterface $environment): void
    {
        $definition      = $environment->getDataDefinition();
        $basicDefinition = $definition->getBasicDefinition();
        $dataProvider    = $environment->getDataProvider();

        if ('mm_ferienpass' !== $definition->getName()) {
            return;
        }

        switch ($environment->getInputProvider()->getParameter('act')) {
            case 'edit':
            case 'delete':
                $modelId = ModelId::fromSerialized($environment->getInputProvider()->getParameter('id'));
                break;
            case 'createvariant':
                $modelId = ModelId::fromSerialized($environment->getInputProvider()->getParameter('source'));
                break;

            default:
                // Copy is allowed. The others assumably as well.
                return;
        }

        /** @var Model $model */
        $model = $dataProvider->fetch($dataProvider->getEmptyConfig()->setId($modelId->getId()));

        if (false === $this->offerIsEditableForHost($model->getItem())) {
            $basicDefinition->setEditable(false);
            $basicDefinition->setCreatable(false);
            $basicDefinition->setDeletable(false);
        }
    }


    /**
     * Check whether one offer is editable for the host by checking the edit deadline.
     *
     * @param IItem $offer The offer.
     *
     * @return bool
     */
    private function offerIsEditableForHost(IItem $offer): bool
    {
        $passEditionId = $offer->get('pass_edition')['id'];
        if (!$passEditionId) {
            throw new \UnexpectedValueException('pass_edition is not set for offer ID ' . $offer->get('id'));
        }

        $passEdition      = $this->doctrine->getRepository(PassEdition::class)->find($passEditionId);
        $hostEditingStage = $passEdition->getCurrentHostEditingStage();

        return null !== $hostEditingStage;
    }
}
