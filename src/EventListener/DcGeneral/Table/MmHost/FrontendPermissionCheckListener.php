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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmHost;


use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\FrontendUser;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\DcGeneral\Event\PreEditModelEvent;
use MetaModels\DcGeneral\Data\Model;

class FrontendPermissionCheckListener
{

    /**
     * @var RequestScopeDeterminator
     */
    private $scopeMatcher;

    /**
     * FrontendPermissionCheckListener constructor.
     *
     * @param RequestScopeDeterminator $scopeMatcher
     */
    public function __construct(RequestScopeDeterminator $scopeMatcher)
    {
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * @param PreEditModelEvent $event The event.
     *
     * @throws AccessDeniedException
     */
    public function handle(PreEditModelEvent $event)
    {
        $environment  = $event->getEnvironment();
        $definition   = $environment->getDataDefinition();
        $dataProvider = $definition->getBasicDefinition()->getDataProvider();
        $model        = $event->getModel();

        if (!$model instanceof Model
            || 'mm_host' !== $dataProvider
            || !$this->scopeMatcher->currentScopeIsFrontend()) {
            return;
        }

        /** @var FrontendUser $user */
        $user = FrontendUser::getInstance();
        if ($model->getItem()->get('id') !== $user->ferienpass_host) {
            throw new AccessDeniedException('Access denied for: '.ModelId::fromModel($model)->getSerialized());
        }
    }
}
