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


use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetOperationButtonEvent;
use MetaModels\DcGeneral\Data\Model;
use MetaModels\IItem;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;

class AttendancesOperationButtonListener
{

    /**
     * Remove the "edit attendances" operation for variant bases
     *
     * @param GetOperationButtonEvent $event
     */
    public function handle(GetOperationButtonEvent $event)
    {
        /** @var Model $model */
        $model = $event->getModel();

        if ('edit_attendances' !== $event->getCommand()->getName()
            || 'mm_ferienpass' !== $model->getProviderName()
        ) {
            return;
        }

        $item = $model->getItem();

        if (!$item instanceof IItem) {
            return;
        }

        // Disable action for variant bases
        if ($item->isVariantBase() && 0 !== $item->getVariants(null)->getCount()) {
            $event->setDisabled(true);
        }

        if (!$item->get('applicationlist_active')) {
            // Does not use the application system
            $event->setDisabled(true);
        } elseif (0 === Attendance::countByOffer($item->get('id'))) {
            // No attendances at all
            $event->setAttributes(
                sprintf('%s data-applicationlist-state="no-attendances"', $event->getAttributes())
            );
        } elseif (0 === Attendance::countByOfferAndStatus($item->get('id'), AttendanceStatus::findWaiting()->id)
        ) {
            // No attendances with `waiting` status
            $event->setAttributes(
                sprintf('%s data-applicationlist-state="all-assigned"', $event->getAttributes())
            );
        } else {
            // Needs further assignments
            $event->setAttributes(
                sprintf('%s data-applicationlist-state="needs-reassignments"', $event->getAttributes())
            );
        }
    }
}

