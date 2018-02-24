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

namespace Richardhj\ContaoFerienpassBundle\EventListener\HostEditing;


use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\DecodePropertyValueForWidgetEvent;
use MetaModels\ViewCombination\ViewCombination;

class LoadDefaultInfotableListener
{

    /**
     * @var RequestScopeDeterminator
     */
    private $scopeMatcher;

    /**
     * @var ViewCombination
     */
    private $viewCombination;

    /**
     * LoadDefaultInfotableListener constructor.
     *
     * @param RequestScopeDeterminator $scopeMatcher    The request scope determinator.
     * @param ViewCombination          $viewCombination The current view combination.s
     */
    public function __construct(RequestScopeDeterminator $scopeMatcher, ViewCombination $viewCombination)
    {
        $this->scopeMatcher    = $scopeMatcher;
        $this->viewCombination = $viewCombination;
    }

    /**
     * @param DecodePropertyValueForWidgetEvent $event
     *
     * @return void
     */
    public function handle(DecodePropertyValueForWidgetEvent $event): void
    {
        $property = $event->getProperty();
        $screen   = $this->viewCombination->getScreen('mm_ferienpass');

        if ('infotable' !== $property
            || '3' !== $screen['meta']['id']
            || false === $this->scopeMatcher->currentScopeIsFrontend()
            || [] !== $event->getValue()) {
            return;
        }

        $event->setValue(
            [
                [
                    'col_0' => 'Ort',
                    'col_1' => '',
                ],
                [
                    'col_0' => 'Wegbeschreibung',
                    'col_1' => '',
                ],
                [
                    'col_0' => 'Mitzubringen',
                    'col_1' => '',
                ],
                [
                    'col_0' => 'Zu beachten',
                    'col_1' => '',
                ],
                [
                    'col_0' => 'Auskunft unter',
                    'col_1' => '',
                ],
            ]
        );
    }
}
