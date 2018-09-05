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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmFerienpass;


use Contao\CoreBundle\Exception\AccessDeniedException;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Event\PreDuplicateModelEvent;
use MetaModels\DcGeneral\Data\Model;
use Richardhj\ContaoFerienpassBundle\Entity\PassEdition;
use Richardhj\ContaoFerienpassBundle\Exception\NoPassEditionWithActiveHostEditingStageException;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Class FeeUpdatePassEditionOnDuplicateListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmFerienpass
 */
class FeeUpdatePassEditionOnDuplicateListener
{

    /**
     * The request scope determinator.
     *
     * @var RequestScopeDeterminator
     */
    private $scopeMatcher;

    /**
     * Doctrine.
     *
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * FrontendPermissionCheckListener constructor.
     *
     * @param RequestScopeDeterminator $scopeMatcher The request scope determinator.
     * @param ManagerRegistry          $doctrine     Doctrine.
     */
    public function __construct(RequestScopeDeterminator $scopeMatcher, ManagerRegistry $doctrine)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->doctrine     = $doctrine;
    }

    /**
     * @param PreDuplicateModelEvent $event The event.
     *
     * @throws AccessDeniedException
     */
    public function handle(PreDuplicateModelEvent $event): void
    {
        $environment  = $event->getEnvironment();
        $definition   = $environment->getDataDefinition();
        $dataProvider = $definition->getBasicDefinition()->getDataProvider();
        $model        = $event->getModel();

        if (!$model instanceof Model
            || 'mm_ferienpass' !== $dataProvider
            || !$this->scopeMatcher->currentScopeIsFrontend()) {
            return;
        }

        $passEdition = $this->doctrine->getManager()->getRepository(PassEdition::class)->findOneToEdit();
        if (null === $passEdition) {
            throw new NoPassEditionWithActiveHostEditingStageException(
                'No pass edition with active host editing stage found.'
            );
        }

        $item = $model->getItem();
        $item->set('pass_edition', ['id' => $passEdition->getId()]);
        $item->set('date_period', null);
    }
}
