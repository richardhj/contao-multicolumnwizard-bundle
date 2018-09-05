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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table;


use ContaoCommunityAlliance\DcGeneral\Contao\InputProvider;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\BasicDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\PopulateEnvironmentEvent;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

/**
 * Class AbstractAttendancePopulateEnvironmentListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table
 */
abstract class AbstractAttendancePopulateEnvironmentListener
{

    /**
     * Make the "attendances" table editable as a child table of the offer or participant
     *
     * @param PopulateEnvironmentEvent $event The event.
     *
     * @throws \ContaoCommunityAlliance\DcGeneral\Exception\DcGeneralRuntimeException
     */
    public function handle(PopulateEnvironmentEvent $event): void
    {
        $environment   = $event->getEnvironment();
        $definition    = $environment->getDataDefinition();
        $inputProvider = new InputProvider();

        if (null === ($pid = $inputProvider->getParameter('pid'))
            || $definition->getName() !== Attendance::getTable()
        ) {
            return;
        }

        $modelId = ModelId::fromSerialized($pid);

        // Set parented list mode
        $environment->getDataDefinition()->getBasicDefinition()->setMode(BasicDefinitionInterface::MODE_PARENTEDLIST);
        // Set parent data provider corresponding to pid
        $definition->getBasicDefinition()->setParentDataProvider($modelId->getDataProviderName());
    }
}
