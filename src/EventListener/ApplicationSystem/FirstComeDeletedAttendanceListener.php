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
    public function handle(DeleteModelEvent $event)
    {
        if (!$this->applicationSystem instanceof FirstCome) {
            return;
        }

        $attendance = $event->getModel();
        if (!$attendance instanceof Attendance) {
            return;
        }

        Attendance::updateStatusByOffer($attendance->getOffer()->get('id'));
    }
}

