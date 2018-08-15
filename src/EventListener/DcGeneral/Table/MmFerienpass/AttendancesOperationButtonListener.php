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
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\Lot;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;
use Richardhj\ContaoFerienpassBundle\Model\Offer as OfferModel;

class AttendancesOperationButtonListener
{
    /**
     * @var OfferModel
     */
    private $offerModel;

    /**
     * AttendancesOperationButtonListener constructor.
     *
     * @param OfferModel $offerModel
     */
    public function __construct(OfferModel $offerModel)
    {
        $this->offerModel = $offerModel;
    }

    /**
     * Alter the "edit attendances" button.
     *
     * @param GetOperationButtonEvent $event
     */
    public function handle(GetOperationButtonEvent $event): void
    {
        /** @var Model $model */
        $model = $event->getModel();

        if ('mm_ferienpass' !== $model->getProviderName()
            || 'edit_attendances' !== $event->getCommand()->getName()
        ) {
            return;
        }

        $item = $model->getItem();
        if (!$item instanceof IItem) {
            return;
        }

        $applicationSystem = $this->offerModel->getApplicationSystem($item);
        if (!($applicationSystem instanceof Lot)) {
            return;
        }

        // Disable action for variant bases
        $variants = $item->getVariants(null);
        if (null === $variants) {
            // We have a variant here
            return;
        }

        if ($item->isVariantBase() && 0 !== $variants->getCount()) {
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
