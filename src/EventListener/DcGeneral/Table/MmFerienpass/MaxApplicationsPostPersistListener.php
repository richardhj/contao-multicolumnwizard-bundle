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


use ContaoCommunityAlliance\DcGeneral\Event\PostPersistModelEvent;
use MetaModels\DcGeneral\Data\Model;
use MetaModels\IItem;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

class MaxApplicationsPostPersistListener
{

    /**
     * Update the attendances when changing the "applicationlist_max" value
     *
     * @param PostPersistModelEvent $event The event.
     */
    public function handle(PostPersistModelEvent $event): void
    {
        $model         = $event->getModel();
        $originalModel = $event->getOriginalModel();
        if (null === $originalModel) {
            return;
        }

        if (!$model instanceof Model
            || 'mm_ferienpass' !== $event->getModel()->getProviderName()
            || $model->getProperty('applicationlist_max') === $originalModel->getProperty('applicationlist_max')
        ) {
            return;
        }

        $ids = [$model->getId()];
        if ($model->getItem()->isVariantBase()) {
            $variants = $model->getItem()->getVariants(null);
            $ids      = array_merge(
                array_map(
                    function (IItem $item) {
                        return $item->get('id');
                    },
                    iterator_to_array($variants)
                ),
                $ids
            );
        }

        foreach ($ids as $id) {
            Attendance::updateStatusByOffer($id);
        }
    }
}
