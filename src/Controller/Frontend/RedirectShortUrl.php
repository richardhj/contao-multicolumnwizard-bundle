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

namespace Richardhj\ContaoFerienpassBundle\Controller\Frontend;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\Model;
use Contao\PageModel;
use Contao\System;
use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\GenerateFrontendUrlEvent;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IFactory;
use MetaModels\Item;
use MetaModels\Render\Setting\IRenderSettingFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * This controller handles the redirect of short urls /{id}.
 */
class RedirectShortUrl
{

    /**
     * The MetaModels factory.
     *
     * @var IFactory
     */
    private $factory;

    /**
     * The MetaModels render setting factory.
     *
     * @var IRenderSettingFactory
     */
    private $renderSettingFactory;

    /**
     * The event dispatcher.
     *
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * The render setting ID of the list view.
     *
     * @var int
     */
    private $listViewId;

    /**
     * The page ID of the list view.
     *
     * @var int
     */
    private $listPageId;

    /**
     * RedirectShortUrl constructor.
     *
     * @param IFactory                 $factory              The MetaModels factory.
     * @param IRenderSettingFactory    $renderSettingFactory The MetaModels render setting factory.
     * @param EventDispatcherInterface $dispatcher           The event dispatcher.
     * @param int                      $listViewId           The render setting ID of the list view (having jumpToPage
     *                                                       settings).
     * @param int                      $listPageId           The page ID of the list view.
     */
    public function __construct(
        IFactory $factory,
        IRenderSettingFactory $renderSettingFactory,
        EventDispatcherInterface $dispatcher,
        int $listViewId,
        int $listPageId
    ) {
        $this->factory              = $factory;
        $this->dispatcher           = $dispatcher;
        $this->renderSettingFactory = $renderSettingFactory;
        $this->listViewId           = $listViewId;
        $this->listPageId           = $listPageId;
    }

    /**
     * @param int $itemId The MetaModel item id of the offer.
     *
     * @return void
     *
     * @throws \UnexpectedValueException When the item id is not numeric
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws PageNotFoundException
     * @throws RedirectResponseException
     */
    public function __invoke($itemId)
    {
        if (!is_numeric($itemId)) {
            throw new \UnexpectedValueException('Item ID is no ID, something must be broken.');
        }

        $metaModel = $this->factory->getMetaModel('mm_ferienpass');
        if (null === $metaModel) {
            throw new PageNotFoundException('MetaModel could not be found.');
        }

        $filter         = $metaModel->getEmptyFilter();
        $filterVariants = $filter->createCopy();

        $filter->addFilterRule(new StaticIdList([$itemId]));
        $item = $metaModel->findByFilter($filter)->getItem();

        if (null !== $item) {
            $variants = $item->getVariants($filterVariants);

            if ($item instanceof Item && (null === $variants || 0 === $variants->getCount())) {
                // Redirect directly to the reader page
                $view       = $this->renderSettingFactory->createCollection($item->getMetaModel(), $this->listViewId);
                $jumpToLink = $item->buildJumpToLink($view);
                throw new RedirectResponseException($jumpToLink['url'], 301);
            }

            // Redirect to the list page with the vargroup as filter
            /** @var PageModel|Model $jumpTo */
            $jumpTo = PageModel::findById($this->listPageId);
            if (null === $jumpTo) {
                throw new PageNotFoundException('List page could not be found: ' . $this->listPageId);
            }

            $params   = '/vargroup/' . $item->get('vargroup') . '#jumpToMmList';
            $urlEvent = new GenerateFrontendUrlEvent($jumpTo->row(), $params);
            $this->dispatcher->dispatch(
                ContaoEvents::CONTROLLER_GENERATE_FRONTEND_URL,
                $urlEvent
            );
            throw new RedirectResponseException($urlEvent->getUrl(), 301);
        }

        throw new PageNotFoundException(
            'MetaModel item not found: ' . ModelId::fromValues($metaModel->getTableName(), $itemId)
                ->getSerialized()
        );
    }
}
