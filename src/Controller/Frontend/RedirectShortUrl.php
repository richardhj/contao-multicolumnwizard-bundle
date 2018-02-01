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

namespace Richardhj\ContaoFerienpassBundle\Controller\Frontend;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\Model;
use Contao\PageModel;
use Contao\System;
use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\GenerateFrontendUrlEvent;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use MetaModels\Factory;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\Item;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * This controller handles the redirect of short urls /{id}.
 */
class RedirectShortUrl
{

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * RedirectShortUrl constructor.
     *
     * @param Factory                  $factory    The MetaModels factory.
     * @param EventDispatcherInterface $dispatcher The event dispatcher.
     */
    public function __construct(Factory $factory, EventDispatcherInterface $dispatcher)
    {
        $this->factory    = $factory;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param int     $itemId  The MetaModel item id of the offer.
     * @param Request $request The request.
     *
     * @return void
     *
     * @throws \UnexpectedValueException When the item id is not numeric
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws \Contao\CoreBundle\Exception\PageNotFoundException
     * @throws RedirectResponseException
     */
    public function __invoke($itemId, Request $request)
    {
        if (!is_numeric($itemId)) {
            throw new \UnexpectedValueException('Item ID is no ID, something must be broken.');
        }

        $metaModel = $this->factory->getMetaModel('mm_ferienpass');
        if (null === $metaModel) {
            throw new PageNotFoundException('MetaModel could not be found.');
        }

        $viewId         = System::getContainer()->getParameter('richardhj.ferienpass.metamodel_list.view.id');
        $listPageId     = System::getContainer()->getParameter('richardhj.ferienpass.list_page.id');
        $filter         = $metaModel->getEmptyFilter();
        $filterVariants = clone $filter;

        $filter->addFilterRule(new StaticIdList([$itemId]));
        $item = $metaModel->findByFilter($filter)->getItem();

        if (null !== $item) {
            $variants = $item->getVariants($filterVariants);

            if ($item instanceof Item && (null === $variants || 0 === $variants->getCount())) {
                // Redirect directly to the reader page
                $url = $item->buildJumpToLink($item->getMetaModel()->getView($viewId))['url'];
                throw new RedirectResponseException($url, 301);
            }

            // Redirect to the list page with the vargroup as filter
            /** @var PageModel|Model $jumpTo */
            $jumpTo = PageModel::findById($listPageId);
            if (null === $jumpTo) {
                throw new PageNotFoundException('List page could not be found: '.$listPageId);
            }

            $params   = '/vargroup/'.$item->get('vargroup').'#jumpToMmList';
            $urlEvent = new GenerateFrontendUrlEvent($jumpTo->row(), $params);
            $this->dispatcher->dispatch(
                ContaoEvents::CONTROLLER_GENERATE_FRONTEND_URL,
                $urlEvent
            );
            throw new RedirectResponseException($urlEvent->getUrl(), 301);
        }

        throw new PageNotFoundException(
            'MetaModel item not found: '.ModelId::fromValues($metaModel->getTableName(), $item->get('id'))
                ->getSerialized()
        );
    }
}
