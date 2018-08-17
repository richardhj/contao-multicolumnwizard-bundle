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


namespace Richardhj\ContaoFerienpassBundle\EventListener\ItemList;


use MetaModels\Events\ItemListModifyFilterEvent;
use MetaModels\Events\RenderItemListEvent;
use MetaModels\Filter\Rules\StaticIdList;
use Richardhj\ContaoFerienpassBundle\Entity\PassEdition;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class ModifyPassEditionFilterParameterListener
{

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * ModifyPassEditionFilterParameterListener constructor.
     *
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param RenderItemListEvent $event The event.
     *
     * @return void
     */
    public function handleRenderItemList(RenderItemListEvent $event): void
    {
        $caller = $event->getCaller();
        if (null === $caller || 'mm_ferienpass' !== $event->getList()->getMetaModel()->getTableName()) {
            return;
        }

        // Mark this list to be modified in filtering.
        $event->getList()->getView()->set(
            '$mm-list-show-offers',
            'show_offers' === $caller->metamodel_list_ferienpass_task
        );
    }

    /**
     * @param ItemListModifyFilterEvent $event
     */
    public function handleForFrontendList(ItemListModifyFilterEvent $event): void
    {
        $itemList = $event->getItemList();
        $view     = $itemList->getView();

        // Do not modify filtering for this list.
        if (!$view->get('$mm-list-show-offers')) {
            return;
        }

        $passEdition = $this->doctrine->getRepository(PassEdition::class)->findOneToShowInFrontend();
        if (null === $passEdition) {
            // To make this work, "Allow empty value" has to be enabled.
            // Finally force an empty result set, if we are not able to determine the pass edition.
            $itemList->getFilter()->addFilterRule(new StaticIdList([]));
        }

        $itemList->getFilterSettings()->addRules(
            $itemList->getFilter(),
            $passEdition ? ['pass_edition' => $passEdition->getId()] : []
        );
    }
}
