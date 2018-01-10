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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table;


use ContaoCommunityAlliance\DcGeneral\Contao\InputProvider;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\BasicDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\PopulateEnvironmentEvent;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

class AbstractAttendancePopulateEnvironmentListener
{

    /**
     * Make the "attendances" table editable as a child table of the offer or participant
     *
     * @param PopulateEnvironmentEvent $event
     */
    public function handle(PopulateEnvironmentEvent $event)
    {
        $environment   = $event->getEnvironment();
        $definition    = $environment->getDataDefinition();
        $inputProvider = $environment->getInputProvider() ?: new InputProvider(); // FIXME Why is inputProvider null?

        if ($definition->getName() !== Attendance::getTable()
            || null === ($pid = $inputProvider->getParameter('pid'))
        ) {
            return;
        };

        $modelId = ModelId::fromSerialized($pid);

        // Set parented list mode
        $environment->getDataDefinition()->getBasicDefinition()->setMode(BasicDefinitionInterface::MODE_PARENTEDLIST);
        // Set parent data provider corresponding to pid
        $definition->getBasicDefinition()->setParentDataProvider($modelId->getDataProviderName());

        // Remove redundant legend (offer_legend in offer view)
        $palette = $definition->getPalettesDefinition()->getPaletteByName('default');

        switch ($modelId->getDataProviderName()) {
            case 'mm_ferienpass':
                $palette->removeLegend($palette->getLegend('offer'));
                break;

            case 'mm_participant':
                $palette->removeLegend($palette->getLegend('participant'));
                break;
        }
    }
}