<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Module;


use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\GenerateFrontendUrlEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\LogEvent;
use ContaoCommunityAlliance\UrlBuilder\UrlBuilder;
use MetaModels\Attribute\Select\MetaModelSelect;
use MetaModels\Events\ParseItemEvent;
use MetaModels\Events\RenderItemListEvent;
use MetaModels\FrontendIntegration\HybridList;
use MetaModels\IItem;
use MetaModels\Item;
use MetaModels\MetaModelsEvents;
use PageModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class Subscriber implements EventSubscriberInterface
{

    /**
     * This property will get set on the render setting collection.
     */
    const FILTER_PARAMS_FLAG = '$filter-params';


    /**
     * This property will get set on the render setting collection.
     */
    const HOST_EDITING_ENABLED_FLAG = '$host-editing';


    /**
     * This property will get set on the render setting collection.
     */
    const JUMP_TO_APPLICATION_LIST_FLAG = '$jump-to-application-list';


    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            MetaModelsEvents::RENDER_ITEM_LIST => [
                ['alterHostMetaModelList'],
                ['alterFrontendEditingLabelInListRendering'],
            ],
            MetaModelsEvents::PARSE_ITEM       => [
                ['addVariantCssClass'],
                ['alterDetailsLink'],
                ['alterEditLink'],
                ['addApplicationListLink'],
                ['addDeleteLink'],
                ['addCopyLink'],
                ['addCreateVariantLink'],
            ],
        ];
    }


    /**
     * Alter the front end editing labels ('edit', 'add new') in the list rendering corresponding to the MetaModel
     *
     * @param RenderItemListEvent $event
     */
    public function alterFrontendEditingLabelInListRendering(RenderItemListEvent $event)
    {
        $caller = $event->getCaller();
        if (!($caller instanceof HybridList)) {
            return;
        }

        switch ($event->getList()->getMetaModel()->getTableName()) {
            case 'mm_ferienpass':
//                $event->getTemplate()->editLabel = $GLOBALS['TL_LANG']['MSC']['editOffer'];
                $caller->Template->addNewLabel = $GLOBALS['TL_LANG']['MSC']['addNewOffer'];
                break;

            case 'mm_participant':
//                $event->getTemplate()->editLabel = $GLOBALS['TL_LANG']['MSC']['editParticipant'];
                $caller->Template->addNewLabel = $GLOBALS['TL_LANG']['MSC']['addNewParticipant'];
                break;

            case 'mm_host':
//                $event->getTemplate()->editLabel = $GLOBALS['TL_LANG']['MSC']['editHost'];
                break;
        }
    }


    /**
     * Alter the host MetaModel list
     *
     * @param RenderItemListEvent $event
     */
    public function alterHostMetaModelList(RenderItemListEvent $event)
    {
        $caller = $event->getCaller();
        if ('mm_ferienpass' !== $event->getList()->getMetaModel()->getTableName()
            || !($caller instanceof HostEditingList)
        ) {
            return;
        }

        $event->getList()->getView()->set(self::HOST_EDITING_ENABLED_FLAG, true);
        $event->getList()->getView()->set(self::FILTER_PARAMS_FLAG, deserialize($caller->metamodel_filterparams));
        $event->getList()->getView()->set(self::JUMP_TO_APPLICATION_LIST_FLAG, $caller->jumpTo_application_list);
    }


    public function addVariantCssClass(ParseItemEvent $event)
    {
        $settings = $event->getRenderSettings();
        if ('mm_ferienpass' !== $event->getItem()->getMetaModel()->getTableName()
            || !$settings->get(self::HOST_EDITING_ENABLED_FLAG)
        ) {
            return;
        }

        $parsed = $event->getResult();
        if (!$event->getItem()->isVariantBase()) {
            $parsed['class'] .= ' isvariant';
        } elseif ($event->getItem()->getVariants(null)->getCount()) {
            $parsed['class'] .= ' isvariantbase';
        }

        $event->setResult($parsed);
    }


    /**
     * Add the details link to the host list
     *
     * @param ParseItemEvent $event
     */
    public function alterDetailsLink(ParseItemEvent $event)
    {
        $settings = $event->getRenderSettings();
        if ('mm_ferienpass' !== $event->getItem()->getMetaModel()->getTableName()
            || !$settings->get(self::HOST_EDITING_ENABLED_FLAG)
        ) {
            return;
        }

        $parsed = $event->getResult();

        if ($event->getItem()->isVariantBase() && $event->getItem()->getVariants(null)->getCount()) {
            unset($parsed['actions']['jumpTo']);
        } else {
            $parsed['actions']['jumpTo']['attribute'] = 'data-lightbox';
        }

        $event->setResult($parsed);
    }


    /**
     * Add the edit link to the host list
     *
     * @param ParseItemEvent $event
     */
    public function alterEditLink(ParseItemEvent $event)
    {
        $settings = $event->getRenderSettings();
        if ('mm_ferienpass' !== $event->getItem()->getMetaModel()->getTableName()
            || !$settings->get(self::HOST_EDITING_ENABLED_FLAG)
            || self::offerIsEditableForHost($event->getItem())
        ) {
            return;
        }

        $result = $event->getResult();
        $result['actions']['edit']['class'] .= ' disabled';

        $event->setResult($result);
    }


    /**
     * Add the application list link to the host list
     *
     * @param ParseItemEvent $event
     */
    public function addApplicationListLink(ParseItemEvent $event)
    {
        global $container;

        /** @var Item $passRelease */
        $passRelease  = $container['ferienpass.pass-release.show-current'];
        $settings     = $event->getRenderSettings();
        $filterParams = $settings->get(self::FILTER_PARAMS_FLAG);
        /** @var \Model\Collection $jumpTo */
        $jumpTo = PageModel::findByPk($settings->get(self::JUMP_TO_APPLICATION_LIST_FLAG));

        if ('mm_ferienpass' !== $event->getItem()->getMetaModel()->getTableName()
            || null === $passRelease
            || null === $jumpTo
            || !$settings->get(self::HOST_EDITING_ENABLED_FLAG)
            || !$event->getItem()->get('applicationlist_active')
            || $passRelease->get('id') !== $filterParams['pass_release']['value']
            || !($event->getItem()->isVariantBase() && !$event->getItem()->getVariants(null)->getCount())
        ) {
            return;
        }

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $container['event-dispatcher'];

        $parsed = $event->getResult();

        $generateFrontendUrlEvent = new GenerateFrontendUrlEvent($jumpTo->row(), '/' . $parsed['raw']['alias']);
        $dispatcher->dispatch(
            ContaoEvents::CONTROLLER_GENERATE_FRONTEND_URL,
            $generateFrontendUrlEvent
        );
        $url = $generateFrontendUrlEvent->getUrl();

        $parsed['actions'][] = [
            'label' => $GLOBALS['TL_LANG']['MSC']['applicationlistLink'][0],
            'title' => $GLOBALS['TL_LANG']['MSC']['applicationlistLink'][1],
            'class' => 'applicationlist',
            'href'  => $url,
        ];

        $event->setResult($parsed);
    }


    /**
     * Add the delete link to the host list
     *
     * @param ParseItemEvent $event
     */
    public function addDeleteLink(ParseItemEvent $event)
    {
        $settings = $event->getRenderSettings();
        if ('mm_ferienpass' !== $event->getItem()->getMetaModel()->getTableName()
            || !$settings->get(self::HOST_EDITING_ENABLED_FLAG)
        ) {
            return;
        }

        $result = $event->getResult();
        $button = [
            'label'     => $GLOBALS['TL_LANG']['MSC']['deleteLink'][0],
            'title'     => $GLOBALS['TL_LANG']['MSC']['deleteLink'][1],
            'class'     => 'delete',
            'href'      => strtok(\Environment::get('request'), '?') .
                           sprintf(
                               '?action=delete::%u::%s',
                               $result['raw']['id'],
                               REQUEST_TOKEN
                           ),
            'attribute' => 'onclick="return confirm(\'' . sprintf(
                    $GLOBALS['TL_LANG']['MSC']['itemConfirmDeleteLink'],
                    $result['raw']['name']
                ) . '\')"',
        ];

        if (!self::offerIsEditableForHost($event->getItem())) {
            $button['class'] .= ' disabled';
        }

        $result['actions'][] = $button;
        $event->setResult($result);
    }


    /**
     * Add the copy link to the host list
     *
     * @param ParseItemEvent $event
     */
    public function addCopyLink(ParseItemEvent $event)
    {
        global $container;

        /** @var Item $passRelease */
        $passRelease  = $container['ferienpass.pass-release.edit-previous'];
        $settings     = $event->getRenderSettings();
        $filterParams = $settings->get(self::FILTER_PARAMS_FLAG);

        if ('mm_ferienpass' !== $event->getItem()->getMetaModel()->getTableName()
            || null === $passRelease
            || !$settings->get(self::HOST_EDITING_ENABLED_FLAG)
            || $passRelease->get('id') !== $filterParams['pass_release']['value']
            || $event->getItem()->isVariant()
        ) {
            return;
        }


        $parsed = $event->getResult();
        $button = [
            'label' => $GLOBALS['TL_LANG']['MSC']['copyLink'][0],
            'title' => $GLOBALS['TL_LANG']['MSC']['copyLink'][1],
            'class' => 'copy',
            'href'  => str_replace('act=edit', 'act=copy', $parsed['editUrl']),
        ];

        if (!self::offerIsEditableForHost($event->getItem())) {
            $button['class'] .= ' disabled';
        }

        $parsed['actions'][] = $button;
        $event->setResult($parsed);
    }


    /**
     * Add the create variant link to the host list
     *
     * @param ParseItemEvent $event
     */
    public function addCreateVariantLink(ParseItemEvent $event)
    {
        global $container;

        /** @var Item $passRelease */
        $passRelease  = $container['ferienpass.pass-release.edit-current'];
        $settings     = $event->getRenderSettings();
        $filterParams = $settings->get(self::FILTER_PARAMS_FLAG);

        if ('mm_ferienpass' !== $event->getItem()->getMetaModel()->getTableName()
            || null === $passRelease
            || !$settings->get(self::HOST_EDITING_ENABLED_FLAG)
            || $passRelease->get('id') !== $filterParams['pass_release']['value']
            || !($event->getItem()->isVariantBase() && $event->getItem()->getVariants(null)->getCount())
        ) {
            return;
        }

        $parsed = $event->getResult();

        $urlBuilder = UrlBuilder::fromUrl($parsed['actions']['edit']['href']);
        $urlBuilder
            ->setQueryParameter('act', 'create')
            ->setQueryParameter('vargroup', $urlBuilder->getQueryParameter('id'))
            ->unsetQueryParameter('id');

        $button = [
            'label' => $GLOBALS['TL_LANG']['MSC']['createVariantLink'][0],
            'title' => $GLOBALS['TL_LANG']['MSC']['createVariantLink'][1],
            'class' => 'createVariant',
            'href'  => $urlBuilder->getUrl(),
        ];

        if (!self::offerIsEditableForHost($event->getItem())) {
            $button['class'] .= ' disabled';
        }

        $parsed['actions'][] = $button;
        $event->setResult($parsed);
    }


    /**
     * Check whether one offer is editable for the host by checking the edit deadline
     *
     * @param IItem $offer
     *
     * @return bool
     */
    private static function offerIsEditableForHost(IItem $offer)
    {
        return (time() <= self::getHostEditEnd($offer));
    }


    /**
     * Get the host edit deadline for a pacific offer
     *
     * @param IItem $offer
     *
     * @return mixed
     */
    private static function getHostEditEnd(IItem $offer)
    {
        $passRelease = $offer->get('pass_release');
        if (null === $passRelease) {
            $offer->getMetaModel()->getServiceContainer()->getEventDispatcher()->dispatch(
                ContaoEvents::SYSTEM_LOG,
                new LogEvent(
                    sprintf(
                        'Could not access the pass_release property'
                    ),
                    __METHOD__,
                    TL_ERROR
                )
            );

            return null;
        }

        return $passRelease[MetaModelSelect::SELECT_RAW]['host_edit_end'];
    }
}
