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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmFerienpass;


use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\FrontendUser;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Event\PreEditModelEvent;
use Doctrine\Common\Persistence\ManagerRegistry;
use MetaModels\DcGeneral\Data\Model;
use Richardhj\ContaoFerienpassBundle\Entity\PassEdition;
use Richardhj\ContaoFerienpassBundle\Exception\NoPassEditionWithActiveHostEditingStageException;

class FeeInitialDataListener
{

    /**
     * @var RequestScopeDeterminator
     */
    private $scopeMatcher;

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * FrontendPermissionCheckListener constructor.
     *
     * @param RequestScopeDeterminator $scopeMatcher
     * @param ManagerRegistry          $doctrine
     */
    public function __construct(RequestScopeDeterminator $scopeMatcher, ManagerRegistry $doctrine)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->doctrine     = $doctrine;
    }

    /**
     * @param PreEditModelEvent $event The event.
     *
     * @throws AccessDeniedException
     */
    public function handle(PreEditModelEvent $event): void
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

        $item      = $model->getItem();
        $attribute = $item->getMetaModel()->getAttribute('host');

        /** @var FrontendUser $user */
        $user = FrontendUser::getInstance();

        // Set current ferienpass host.
        if (null === $item->get('host')) {
            $item->set('host', $attribute->widgetToValue($user->ferienpass_host, $item));
        }

        // Set current pass_release.
        $passEdition = $this->doctrine->getManager()->getRepository(PassEdition::class)->findOneToEdit();
        if (null === $passEdition) {
            throw new NoPassEditionWithActiveHostEditingStageException(
                'No pass edition with active host editing stage found.'
            );
        }

        if (null === $item->get('pass_edition')) {
            $item->set('pass_edition', ['id' => $passEdition->getId()]);
        }
    }
}
