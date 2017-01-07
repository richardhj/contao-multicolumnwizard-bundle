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


use MetaModels\Attribute\Select\MetaModelSelect;
use MetaModels\Events\RenderItemListEvent;
use MetaModels\FrontendIntegration\HybridList;
use MetaModels\MetaModelsEvents;
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
            MetaModelsEvents::RENDER_ITEM_LIST => [
                ['alterHostMetaModelList'],
                ['alterFrontendEditingLabelInListRendering'],
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


    public function alterHostMetaModelList(RenderItemListEvent $event)
    {
        if (!($event->getCaller() instanceof HybridList) ||
            'metamodel_multiple_buttons' !== $event->getTemplate()->getName()
        ) {
            return;
        }

        $event->getTemplate()->getButtons = function ($itemData) use ($event) {
            $return = [];
            $buttons =
                [
                    'details',
                    'edit',
                    'applicationlist',
                    'delete',
                ];

            $item = $event
                ->getList()
                ->getServiceContainer()
                ->getFactory()
                ->getMetaModel(
                    $event
                        ->getList()
                        ->getServiceContainer()
                        ->getFactory()
                        ->translateIdToMetaModelName($event->getCaller()->metamodel)
                )
                ->findById($itemData['raw']['id']);

            // Disable specific buttons for items with variants
            if ($item->isVariantBase() && $item->getVariants(null)->getCount()) {
                unset($buttons[array_search('details', $buttons)]);
                unset($buttons[array_search('applicationlist', $buttons)]);
            }

            // Disable application list if not active
            if (!$item->get('applicationlist_active')) {
                unset($buttons[array_search('applicationlist', $buttons)]);
            }

            // Disable buttons if ferienpass is live
            if (time() > $item->get('pass_release')[MetaModelSelect::SELECT_RAW]['host_edit_end']) {
                unset($buttons[array_search('edit', $buttons)]);
                unset($buttons[array_search('delete', $buttons)]);
            }

            // Add the "copy item" button for last release's items
            $filterParams = deserialize($event->getCaller()->metamodel_filterparams);
            if ($filterParams['pass_release']['value'] == 2) { //@todo configurable
                $buttons[] = 'copy';
            } elseif ($filterParams['pass_release']['value'] == 1
                && $item->isVariantBase()
                && $item->getVariants(null)->getCount()
            ) {
                $buttons[] = 'createVariant';
            }

            foreach ($buttons as $button) {
                $buttonData = [];
                $key = $button.'Link';

                if (in_array($key, get_class_methods(__CLASS__))) {
                    $buttonData['link'] = $GLOBALS['TL_LANG']['MSC'][$key][0];
                    $buttonData['title'] = $GLOBALS['TL_LANG']['MSC'][$key][1] ?: $buttonData['link'];
                    $buttonData['class'] = $button;
                    list ($buttonData['href'], $buttonData['attribute']) = $this->$key($itemData);

                    $return[] = $buttonData;
                }
            }

            return $return;
        };
    }


    /**
     * @param  array $arrItem
     *
     * @return array
     */
    protected function detailsLink($arrItem)
    {
        return [$arrItem['jumpTo']['url'], 'data-lightbox'];
    }


    /**
     * @param  array $itemData
     *
     * @return array
     */
    protected function editLink($itemData)
    {
        return [$itemData['editUrl']];
    }


    /**
     * @param  array $itemData
     *
     * @return array
     */
    protected function copyLink($itemData)
    {
        return [str_replace('act=edit', 'act=copy', $itemData['editUrl'])];
    }


    /**
     * @param  array $itemData
     *
     * @return array
     */
    protected function createVariantLink($itemData)
    {
        return [str_replace(['act=edit', 'id'], ['act=create', 'vargroup'], $itemData['editUrl'])];
    }


    /**
     * @param  array $itemData
     *
     * @return array
     */
    protected function applicationlistLink($itemData)
    {
        $v = 19; // todo configurable
        return [$this->generateJumpToLink($v, $itemData['raw']['alias'])];
    }


    /**
     * @param  array $itemData
     *
     * @return array
     */
    protected function deleteLink($itemData)
    {
        $href = \Frontend::addToUrl(sprintf('action=delete::%u::%s', $itemData['raw']['id'], REQUEST_TOKEN));

        return [
            $href,
            ' onclick="return confirm(\''.sprintf(
                $GLOBALS['TL_LANG']['MSC']['itemConfirmDeleteLink'],
                $itemData['raw']['name']
            ).'\')"',
        ];
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
