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


use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Event\PreEditModelEvent;
use MetaModels\DcGeneral\Data\Model;

/**
 * Class FeeUnsetDateOnCreateVariantListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmFerienpass
 */
class FeeUnsetDateOnCreateVariantListener
{

    /**
     * The request scope determinator.
     *
     * @var RequestScopeDeterminator
     */
    private $scopeMatcher;

    /**
     * FeeRemoveDateOnCreateListener constructor.
     *
     * @param RequestScopeDeterminator $scopeMatcher The request scope determinator.
     */
    public function __construct(RequestScopeDeterminator $scopeMatcher)
    {
        $this->scopeMatcher = $scopeMatcher;
    }

    /**
     * @param PreEditModelEvent $event The event.
     *
     * @throws \RuntimeException
     */
    public function handle(PreEditModelEvent $event): void
    {
        $environment   = $event->getEnvironment();
        $inputProvider = $environment->getInputProvider();
        $definition    = $environment->getDataDefinition();
        $dataProvider  = $definition->getBasicDefinition()->getDataProvider();
        $model         = $event->getModel();

        if (!$model instanceof Model
            || 'mm_ferienpass' !== $dataProvider
            || 'createvariant' !== $inputProvider->getParameter('act')
            || !$this->scopeMatcher->currentScopeIsFrontend()) {
            return;
        }

        // Unset date_period in case of createVariant
        $item = $model->getItem();
        $item->set('date_period', null);
    }
}
