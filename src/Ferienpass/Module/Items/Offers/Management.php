<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 * Copyright (c) 2015-2015 Richard Henkenjohann
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard-ferienpass@henkenjohann.me>
 */

namespace Ferienpass\Module\Items\Offers;

use Contao\Environment;
use Contao\PageModel;
use Ferienpass\Model\Config;
use MetaModels\FrontendIntegration\Module\ModelList;


/**
 * Class OffersAdministration
 * @package Ferienpass\Module
 */
class Management extends ModelList
{

    /**
     * Return the item's buttons
     *
     * @param  array $itemData
     *
     * @return array
     */
    public function getButtons($itemData)
    {
        $return = [];
        $buttons =
            [
                'details',
                'edit',
                'applicationlist',
                'delete',
            ];

        $item = $this
            ->getServiceContainer()
            ->getFactory()
            ->getMetaModel(
                $this
                    ->getServiceContainer()
                    ->getFactory()
                    ->translateIdToMetaModelName($this->metamodel)
            )
            ->findById($itemData['raw']['id']);

        // Disable specific buttons for items with variants
        if ($item->isVariantBase() && $item->getVariants(null)->getCount()) {
            unset($buttons[array_search('details', $buttons)]);
            unset($buttons[array_search('applicationlist', $buttons)]);
        }

        // Disable application list if not active
        if (!$item->get(Config::getInstance()->offer_attribute_applicationlist_active)) {
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
    }


    /**
     * @param  array $arrItem
     *
     * @return array
     */
    protected function detailsLink($arrItem)
    {
        return [$arrItem['jumpTo']['url'], ' data-lightbox=""']; //@todo IF lightbox
    }


    /**
     * @param  array $itemData
     *
     * @return array
     */
    protected function editLink($itemData)
    {
        //@todo configurable
        $attribute = '';
        $jumpTo = $this->jumpTo;

        if (!$itemData['raw']['varbase']) {
//            $attribute = ' data-lightbox="" data-lightbox-iframe="" data-lightbox-reload=""';
            $jumpTo = 36;
        }

        return [$this->generateJumpToLink($jumpTo, $itemData['raw']['alias']), $attribute];
    }


    /**
     * @param  array $itemData
     *
     * @return array
     */
    protected function applicationlistLink($itemData)
    {
        return [$this->generateJumpToLink($this->jumpToApplicationList, $itemData['raw']['alias'])];
    }


    /**
     * @param  array $itemData
     *
     * @return array
     */
    protected function deleteLink($itemData)
    {
        $href = $this->addToUrl(sprintf('action=delete::%u::%s', $itemData['raw']['id'], REQUEST_TOKEN));

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

        $url = ampersand(Environment::get('request'), true);

        /** @var \Model\Collection $target */
        $target = PageModel::findByPk($pageId);

        if (null !== $target) {
            $url = ampersand(
                $this->generateFrontendUrl(
                    $target->row(),
                    ((\Config::get('useAutoItem') && !\Config::get('disableAlias')) ? '/%s' : '/items/%s')
                )
            );
        }

        return sprintf($url, $alias);
    }
}
