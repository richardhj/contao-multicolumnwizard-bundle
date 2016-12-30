<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Module;


use MetaModels\Events\RenderItemListEvent;
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
            MetaModelsEvents::RENDER_ITEM_LIST => 'alterHostMetaModelList',
        ];
    }


    public function alterHostMetaModelList(RenderItemListEvent $event)
    {
        if ('metamodel_multiple_buttons' !== $event->getTemplate()->getName()) {
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
            if (!$item->get(\Ferienpass\Model\Config::getInstance()->offer_attribute_applicationlist_active)) {
                unset($buttons[array_search('applicationlist', $buttons)]);
            }

            //@todo configurable in the backend
            // Disable buttons if ferienpass is live
//        unset($buttons[array_search('edit', $buttons)]);
//        unset($buttons[array_search('delete', $buttons)]);

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
        return [$arrItem['jumpTo']['url']];
    }


    /**
     * @param  array $itemData
     *
     * @return array
     */
    protected function editLink($itemData)
    {
        $v = 4; // todo configurable
        return [$this->generateJumpToLink($v, $itemData['raw']['alias'])];
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
