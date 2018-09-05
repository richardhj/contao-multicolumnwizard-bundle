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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmFerienpass;


use ContaoCommunityAlliance\DcGeneral\Event\PostPersistModelEvent;
use MetaModels\DcGeneral\Data\Model;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IItem;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;

/**
 * Class TriggerSyncPostPersistListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmFerienpass
 */
class TriggerSyncPostPersistListener
{

    /**
     * Trigger data processing when saving an offer.
     *
     * @param PostPersistModelEvent $event The event.
     */
    public function handle(PostPersistModelEvent $event): void
    {
        $model = $event->getModel();

        if (!$model instanceof Model
            || 'mm_ferienpass' !== $model->getProviderName()
        ) {
            return;
        }

        /** @type \Model\Collection|DataProcessing $processing */
        $processing = DataProcessing::findBy('sync', '1');
        while (null !== $processing && $processing->next()) {
            if (!$processing->xml_single_file) {

                $variants = $model->getItem()->getVariants(null);

                $ids = [];
                if (null !== $variants) {
                    $ids = array_map(
                        function (IItem $item) {
                            return $item->get('id');
                        },
                        iterator_to_array($variants)
                    );
                }

                $ids[] = $model->getId();

                $filter = $processing->current()->getFilter();
                if (null !== $filter) {
                    $processing->current()->setFilter($filter->addFilterRule(new StaticIdList($ids)));
                }
            }

            try {
                $processing->current()->run();
            } catch (\Exception $e) {
            }
        }
    }
}
