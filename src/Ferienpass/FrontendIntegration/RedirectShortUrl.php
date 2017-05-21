<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\FrontendIntegration;


use Contao\Environment;
use Contao\Model;
use Contao\PageModel;
use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\GenerateFrontendUrlEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\RedirectEvent;
use ContaoCommunityAlliance\UrlBuilder\UrlBuilder;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\IMetaModelsServiceContainer;
use MetaModels\Item;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Class RedirectShortUrl
 *
 * @package Ferienpass\FrontendIntegration
 */
class RedirectShortUrl
{

    public function handle()
    {
        global $container;

        /** @var IMetaModelsServiceContainer $serviceContainer */
        $serviceContainer = $container['metamodels-service-container'];
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $container['event-dispatcher'];
        $metaModel  = $serviceContainer->getFactory()->getMetaModel('mm_ferienpass');

        // TODO fill this vars properly
        $viewId     = 4;
        $listPageId = 10;
        $filter     = $metaModel->getEmptyFilter();

        $urlBuilder = UrlBuilder::fromUrl(Environment::get('uri'));
        $itemId     = $urlBuilder->getQueryParameter('item_id');
        if (null === $itemId) {
            header('HTTP/1.0 403 Forbidden');
            die_nicely('be_forbidden', 'Invalid request');
        }

        $filter->addFilterRule(new StaticIdList([$itemId]));
        $item = $metaModel->findByFilter($filter)->getItem();

        if (null !== $item) {
            $variants = $item->getVariants($filter);

            if ($item instanceof Item && 0 === $variants->getCount()) {
                // Redirect directly to the reader page
                $url = $item->buildJumpToLink($item->getMetaModel()->getView($viewId))['url'];
                $dispatcher->dispatch(ContaoEvents::CONTROLLER_REDIRECT, new RedirectEvent($url, 301));
            } else {
                // Redirect to the list page with the vargroup as filter
                /** @var PageModel|Model $jumpTo */
                $jumpTo = PageModel::findById($listPageId);

                $params   = '/vargroup/' . $item->get('vargroup') . '#jumpToMmList';
                $urlEvent = new GenerateFrontendUrlEvent($jumpTo->row(), $params);
                $dispatcher->dispatch(
                    ContaoEvents::CONTROLLER_GENERATE_FRONTEND_URL,
                    $urlEvent
                );
                $dispatcher->dispatch(ContaoEvents::CONTROLLER_REDIRECT, new RedirectEvent($urlEvent->getUrl(), 301));
            }
        }

        header('HTTP/1.0 404 Not Found');
        die_nicely('be_no_page', 'Invalid request');
    }
}
