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


use Ferienpass\Event\BuildMetaModelEditingListButtonsEvent;
use MetaModels\Attribute\Select\MetaModelSelect;
use MetaModels\Events\RenderItemListEvent;
use MetaModels\FrontendIntegration\HybridList;
use MetaModels\MetaModelsEvents;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class Subscriber implements EventSubscriberInterface
{

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
            MetaModelsEvents::RENDER_ITEM_LIST          => [
                ['alterHostMetaModelList'],
                ['alterFrontendEditingLabelInListRendering'],
            ],
            BuildMetaModelEditingListButtonsEvent::NAME => [
                ['addDetailsLink'],
                ['addEditLink'],
                ['addApplicationListLink'],
                ['addDeleteLink'],
                ['addCopyLink'],
                ['addCreateVariantLink'],
            ],
        ];
    }


    public function alterFrontendEditingLabelInListRendering(RenderItemListEvent $event)
    {
        $caller = $event->getCaller();

        if (!($caller instanceof HybridList)) {
            return;
        }

        switch ($event->getList()->getMetaModel()->getTableName()) {
            case 'mm_ferienpass':
                $event->getTemplate()->editLabel = $GLOBALS['TL_LANG']['MSC']['editOffer'];
                $caller->Template->addNewLabel = $GLOBALS['TL_LANG']['MSC']['addNewOffer'];
                break;

            case 'mm_participant':
                $event->getTemplate()->editLabel = $GLOBALS['TL_LANG']['MSC']['editParticipant'];
                $caller->Template->addNewLabel = $GLOBALS['TL_LANG']['MSC']['addNewParticipant'];
                break;

            case 'mm_host':
                $event->getTemplate()->editLabel = $GLOBALS['TL_LANG']['MSC']['editHost'];
                break;
        }
    }


    public function alterHostMetaModelList(RenderItemListEvent $renderEvent)
    {
        if (!($renderEvent->getCaller() instanceof HybridList) ||
            'metamodel_multiple_buttons' !== $renderEvent->getTemplate()->getName()
        ) {
            return;
        }

        $renderEvent->getTemplate()->getButtons = function ($itemData) use ($renderEvent) {
            global $container;

            /** @var EventDispatcher $dispatcher */
            $dispatcher = $container['event-dispatcher'];

            $item = $renderEvent
                ->getList()
                ->getServiceContainer()
                ->getFactory()
                ->getMetaModel(
                    $renderEvent
                        ->getList()
                        ->getServiceContainer()
                        ->getFactory()
                        ->translateIdToMetaModelName($renderEvent->getCaller()->metamodel)
                )
                ->findById($itemData['raw']['id']);

            if (null === $item) {
                var_dump($itemData);
                return [];
            }

            $event = new BuildMetaModelEditingListButtonsEvent($item, [], $itemData, $renderEvent->getCaller());
            $dispatcher->dispatch(BuildMetaModelEditingListButtonsEvent::NAME, $event);

            return $event->getButtons();
        };
    }


    public function addDetailsLink(BuildMetaModelEditingListButtonsEvent $event)
    {
        if ($event->getItem()->isVariantBase() && $event->getItem()->getVariants(null)->getCount()) {
            return;
        }

        $buttons = $event->getButtons();

        $button = [
            'link'      => $GLOBALS['TL_LANG']['MSC']['detailsLink'][0],
            'title'     => $GLOBALS['TL_LANG']['MSC']['detailsLink'][1],
            'class'     => 'details',
            'href'      => $event->getItemData()['jumpTo']['url'],
            'attribute' => 'data-lightbox',
        ];
        $buttons[] = $button;

        $event->setButtons($buttons);
    }


    public function addEditLink(BuildMetaModelEditingListButtonsEvent $event)
    {
        if (time() > $event->getItem()->get('pass_release')[MetaModelSelect::SELECT_RAW]['host_edit_end']) {
            return;
        }

        $buttons = $event->getButtons();

        $button = [
            'link'  => $GLOBALS['TL_LANG']['MSC']['editLink'][0],
            'title' => $GLOBALS['TL_LANG']['MSC']['editLink'][1],
            'class' => 'edit',
            'href'  => $event->getItemData()['editUrl'],
        ];
        $buttons[] = $button;

        $event->setButtons($buttons);
    }


    public function addApplicationListLink(BuildMetaModelEditingListButtonsEvent $event)
    {
        $filterParams = deserialize($event->getCaller()->metamodel_filterparams);

        if ($event->getItem()->isVariantBase() && $event->getItem()->getVariants(null)->getCount()
            || !$event->getItem()->get('applicationlist_active')
            || 2 == $filterParams['pass_release']['value']
        ) {
            return;
        }

        $buttons = $event->getButtons();
        $v = 19; // todo configurable

        $button = [
            'link'  => $GLOBALS['TL_LANG']['MSC']['applicationlistLink'][0],
            'title' => $GLOBALS['TL_LANG']['MSC']['applicationlistLink'][1],
            'class' => 'applicationlist',
            'href'  => $this->generateJumpToLink($v, $event->getItemData()['raw']['alias']),
        ];
        $buttons[] = $button;

        $event->setButtons($buttons);
    }


    public function addDeleteLink(BuildMetaModelEditingListButtonsEvent $event)
    {
        if (time() > $event->getItem()->get('pass_release')[MetaModelSelect::SELECT_RAW]['host_edit_end']) {
            return;
        }

        $buttons = $event->getButtons();

        $button = [
            'link'      => $GLOBALS['TL_LANG']['MSC']['deleteLink'][0],
            'title'     => $GLOBALS['TL_LANG']['MSC']['deleteLink'][1],
            'class'     => 'delete',
            'href'      => strtok(\Environment::get('request'), '?').
                sprintf(
                    '?action=delete::%u::%s',
                    $event->getItemData()['raw']['id'],
                    REQUEST_TOKEN
                ),
            'attribute' => 'onclick="return confirm(\''.sprintf(
                    $GLOBALS['TL_LANG']['MSC']['itemConfirmDeleteLink'],
                    $event->getItemData()['raw']['name']
                ).'\')"',
        ];
        $buttons[] = $button;

        $event->setButtons($buttons);
    }


    public function addCopyLink(BuildMetaModelEditingListButtonsEvent $event)
    {
        $filterParams = deserialize($event->getCaller()->metamodel_filterparams);

        if (2 != $filterParams['pass_release']['value']) {
            return;
        }

        $buttons = $event->getButtons();

        $button = [
            'link'      => $GLOBALS['TL_LANG']['MSC']['copyLink'][0],
            'title'     => $GLOBALS['TL_LANG']['MSC']['copyLink'][1],
            'class'     => 'copy',
            'href'      => str_replace('act=edit', 'act=copy', $event->getItemData()['editUrl']),
            'attribute' => '',
        ];
        $buttons[] = $button;

        $event->setButtons($buttons);
    }


    public function addCreateVariantLink(BuildMetaModelEditingListButtonsEvent $event)
    {
        $filterParams = deserialize($event->getCaller()->metamodel_filterparams);

        if (!(1 == $filterParams['pass_release']['value']
            && $event->getItem()->isVariantBase()
            && $event->getItem()->getVariants(null)->getCount())
        ) {
            return;
        }

        $buttons = $event->getButtons();

        $button = [
            'link'      => $GLOBALS['TL_LANG']['MSC']['createVariantLink'][0],
            'title'     => $GLOBALS['TL_LANG']['MSC']['createVariantLink'][1],
            'class'     => 'createVariant',
            'href'      => str_replace(
                ['act=edit', 'id'],
                ['act=create', 'vargroup'],
                $event->getItemData()['editUrl']
            ),
            'attribute' => '',
        ];
        $buttons[] = $button;

        $event->setButtons($buttons);
    }


    /**
     * Create link by given id and item alias
     *
     * @param  integer $pageId
     * @param  string  $alias
     *
     * @return string
     */
    protected function generateJumpToLink($pageId, $alias)
    {
        if ($pageId < 1) {
            return '';
        }

        $url = ampersand(\Environment::get('request'), true);

        /** @var \Model\Collection $target */
        $target = \PageModel::findByPk($pageId);

        if (null !== $target) {
            $url = ampersand(
                \Controller::generateFrontendUrl(
                    $target->row(),
                    ((\Config::get('useAutoItem') && !\Config::get('disableAlias')) ? '/%s' : '/items/%s')
                )
            );
        }

        return sprintf($url, $alias);
    }
}
