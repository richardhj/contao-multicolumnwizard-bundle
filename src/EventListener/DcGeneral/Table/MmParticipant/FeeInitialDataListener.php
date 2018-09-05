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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmParticipant;


use Contao\FrontendUser;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Event\PreEditModelEvent;
use MetaModels\DcGeneral\Data\Model;

/**
 * Class FeeInitialDataListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmParticipant
 */
class FeeInitialDataListener
{

    /**
     * The request scope determinator.
     * @var RequestScopeDeterminator
     */
    private $scopeMatcher;

    /**
     * FrontendPermissionCheckListener constructor.
     *
     * @param RequestScopeDeterminator $scopeMatcher The request scope determinator.
     */
    public function __construct(RequestScopeDeterminator $scopeMatcher)
    {
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * @param PreEditModelEvent $event The event.
     */
    public function handle(PreEditModelEvent $event): void
    {
        $environment  = $event->getEnvironment();
        $definition   = $environment->getDataDefinition();
        $dataProvider = $definition->getBasicDefinition()->getDataProvider();
        $model        = $event->getModel();

        if (!$model instanceof Model
            || 'mm_participant' !== $dataProvider
            || !$this->scopeMatcher->currentScopeIsFrontend()) {
            return;
        }

        $item      = $model->getItem();
        $attribute = $item->getMetaModel()->getAttribute('pmember');

        /** @var FrontendUser $user */
        $user = FrontendUser::getInstance();

        // Set current member id.
        if (null === $item->get('pmember')) {
            $item->set('pmember', $attribute->widgetToValue($user->id, $item));
        }
    }
}
