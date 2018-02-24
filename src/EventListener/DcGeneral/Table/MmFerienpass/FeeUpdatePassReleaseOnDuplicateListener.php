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
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Event\PreDuplicateModelEvent;
use MetaModels\DcGeneral\Data\Model;
use Richardhj\ContaoFerienpassBundle\Util\PassReleases;

class FeeUpdatePassReleaseOnDuplicateListener
{

    /**
     * @var RequestScopeDeterminator
     */
    private $scopeMatcher;

    /**
     * @var PassReleases
     */
    private $passReleases;

    /**
     * FrontendPermissionCheckListener constructor.
     *
     * @param RequestScopeDeterminator $scopeMatcher
     * @param PassReleases             $passReleases
     */
    public function __construct(RequestScopeDeterminator $scopeMatcher, PassReleases $passReleases)
    {
        $this->scopeMatcher = $scopeMatcher;
        $this->passReleases = $passReleases;
    }

    /**
     * @param PreDuplicateModelEvent $event The event.
     *
     * @throws \RuntimeException
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

        $passRelease = $this->passReleases->getPassReleaseToEdit();
        if (null === $passRelease) {
            throw new \RuntimeException('Sorry, can\'t file the pass release.');
        }

        $item = $model->getItem();
        $item->set('pass_release', $passRelease->get('id'));
    }
}
