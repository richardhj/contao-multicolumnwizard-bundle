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

namespace Richardhj\ContaoFerienpassBundle\EventListener\ApplicationSystem;


use Contao\Model\Event\DeleteModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PostDeleteModelEvent;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\FirstCome;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

class FirstComeDeletedAttendanceListener extends AbstractApplicationSystemListener
{

    /**
     * Update all attendance statuses for one offer.
     *
     * @param DeleteModelEvent $event
     *
     * @return void
     */
    public function handle(DeleteModelEvent $event): void
    {
        if (!$this->applicationSystem instanceof FirstCome) {
            return;
        }

        $attendance = $event->getModel();
        if (!$attendance instanceof Attendance) {
            return;
        }

        if ($offer = $attendance->getOffer()) {
            Attendance::updateStatusByOffer($offer->get('id'));
        }
    }

    /**
     * Update all attendance statuses for one offer.
     *
     * @param PostDeleteModelEvent $event
     *
     * @return void
     */
    public function handleDcGeneral(PostDeleteModelEvent $event): void
    {
        $environment = $event->getEnvironment();
        if (!($this->applicationSystem instanceof FirstCome)
            || 'tl_ferienpass_attendance' !== $environment->getDataDefinition()->getName()) {
            return;
        }

        Attendance::updateStatusByOffer($event->getModel()->getProperty('offer'));
    }
}
