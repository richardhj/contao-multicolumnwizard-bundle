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


use ContaoCommunityAlliance\DcGeneral\Contao\DataDefinition\Definition\Contao2BackendViewDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\PopulateEnvironmentEvent;
use Richardhj\ContaoFerienpassBundle\DcGeneral\View\AttendanceAllocationView;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

class SetCustomViewListener
{

    /**
     * @var RequestScopeDeterminator
     */
    private $scopeMatcher;

    /**
     * SetCustomView constructor.
     *
     * @param RequestScopeDeterminator $requestScopeDeterminator The request scope determinator.
     */
    public function __construct(RequestScopeDeterminator $requestScopeDeterminator)
    {
        $this->scopeMatcher = $requestScopeDeterminator;
    }

    /**
     * Use the AttendanceAllocationView if applicable
     *
     * @param PopulateEnvironmentEvent $event
     */
    public function handle(PopulateEnvironmentEvent $event)
    {
        $environment = $event->getEnvironment();

        // Already populated or not in Backend? Get out then.
        if ($environment->getView() || false === $this->scopeMatcher->currentScopeIsBackend()) {
            return;
        }

        $definition = $environment->getDataDefinition();

        // Not attendances for offer MetaModel
        if (!($definition->getName() === Attendance::getTable()
              && 'mm_ferienpass' === $definition->getBasicDefinition()->getParentDataProvider())
            || !$definition->hasBasicDefinition()
        ) {
            return;
        }

        // Set view
        $view = new AttendanceAllocationView($this->scopeMatcher);
        $view->setEnvironment($environment);
        $environment->setView($view);

        // Add "attendances" property
        /** @var Contao2BackendViewDefinitionInterface $viewSection */
        $viewSection = $definition->getDefinition(Contao2BackendViewDefinitionInterface::NAME);
        $listing     = $viewSection->getListingConfig();
        $formatter   = $listing->getLabelFormatter($definition->getName());

        $propertyNames   = $formatter->getPropertyNames();
        $propertyNames[] = 'attendances';
        $formatter->setPropertyNames($propertyNames);
    }
}
