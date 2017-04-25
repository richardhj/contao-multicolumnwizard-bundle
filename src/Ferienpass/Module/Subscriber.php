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
use MetaModels\Events\ParseItemEvent;
use MetaModels\Events\RenderItemListEvent;
use MetaModels\FrontendIntegration\HybridList;
use MetaModels\Item;
use MetaModels\MetaModelsEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class Subscriber implements EventSubscriberInterface
{

    /**
     * This property will get set on the render setting collection.
     */
    const FILTER_PARAMS_FLAG = '$filter-params';


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
                $event->getTemplate()->editLabel = $GLOBALS['TL_LANG']['MSC']['editOffer'];
                $caller->Template->addNewLabel   = $GLOBALS['TL_LANG']['MSC']['addNewOffer'];
                break;

            case 'mm_participant':
                $event->getTemplate()->editLabel = $GLOBALS['TL_LANG']['MSC']['editParticipant'];
                $caller->Template->addNewLabel   = $GLOBALS['TL_LANG']['MSC']['addNewParticipant'];
                break;

            case 'mm_host':
                $event->getTemplate()->editLabel = $GLOBALS['TL_LANG']['MSC']['editHost'];
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
        if (!($caller instanceof HybridList)) {
            return;
        }
        //@TODO check for host metamodel list

        $filterParams = deserialize($caller->metamodel_filterparams);
        $event->getList()->getView()->set(self::FILTER_PARAMS_FLAG, $filterParams);
    }


    /**
     * Add the details link to the host list
     *
     * @param ParseItemEvent $event
     */
    public function alterDetailsLink(ParseItemEvent $event)
    {
        if ('mm_ferienpass' !== $event->getItem()->getMetaModel()->getTableName()) {
            return;
        }

        $result = $event->getResult();

        if ($event->getItem()->isVariantBase() && $event->getItem()->getVariants(null)->getCount()) {
            unset($result['actions']['jumpTo']);
        } else {
            $result['actions']['jumpTo']['attribute'] = 'data-lightbox';
        }

        $event->setResult($result);
    }


    /**
     * Add the edit link to the host list
     *
     * @param ParseItemEvent $event
     */
    public function alterEditLink(ParseItemEvent $event)
    {
        if ('mm_ferienpass' !== $event->getItem()->getMetaModel()->getTableName()) {
            return;
        }

        $result = $event->getResult();
        if (time() > $event->getItem()->get('pass_release')[MetaModelSelect::SELECT_RAW]['host_edit_end']) {
            unset($result['actions']['jumpTo']);
        }

        $event->setResult($result);
    }


    /**
     * Add the application list link to the host list
     *
     * @param ParseItemEvent $event
     */
    public function addApplicationListLink(ParseItemEvent $event)
    {
        if ('mm_ferienpass' !== $event->getItem()->getMetaModel()->getTableName()) {
            return;
        }

        global $container;

        $settings = $event->getRenderSettings();
        if (!$settings->get(self::FILTER_PARAMS_FLAG)) {
            return;
        }

        $parsed = $event->getResult();

        /** @var Item $passRelease */
        $passRelease = $container['ferienpass.pass-release.show-current'];
        if (null === $passRelease) {
            // TODO overthink error handling
            return;
        }

        if ($event->getItem()->isVariantBase() && $event->getItem()->getVariants(null)->getCount()
            || !$event->getItem()->get('applicationlist_active')
            || $passRelease->get('id') != $settings->get(self::FILTER_PARAMS_FLAG)['pass_release']['value']
        ) {
            return;
        }

        $v = 19; // todo configurable

        $parsed['actions'][] = [
            'link'  => $GLOBALS['TL_LANG']['MSC']['applicationlistLink'][0],
            'title' => $GLOBALS['TL_LANG']['MSC']['applicationlistLink'][1],
            'class' => 'applicationlist',
            'href'  => $this->generateJumpToLink($v, $parsed['raw']['alias']),
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
        $a = $event->getItem()->get('pass_release');
        if ('mm_ferienpass' !== $event->getItem()->getMetaModel()->getTableName()
            || time() > $event->getItem()->get('pass_release')[MetaModelSelect::SELECT_RAW]['host_edit_end']
        ) {
            return;
        }

        $result              = $event->getResult();
        $result['actions'][] = [
            'link'      => $GLOBALS['TL_LANG']['MSC']['deleteLink'][0],
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

        $event->setResult($result);
    }


    /**
     * Add the copy link to the host list
     *
     * @param ParseItemEvent $event
     */
    public function addCopyLink(ParseItemEvent $event)
    {
        if ('mm_ferienpass' !== $event->getItem()->getMetaModel()->getTableName()) {
            return;
        }

        global $container;


        /** @var Item $passRelease */
        $passRelease = $container['ferienpass.pass-release.edit-previous'];
        if (null === $passRelease) {
            // TODO overthink error handling
            return;
        }

        $settings = $event->getRenderSettings();
        if (!$settings->get(self::FILTER_PARAMS_FLAG)) {
            return;
        }

        $filterParams = $settings->get(self::FILTER_PARAMS_FLAG);

        if ($passRelease->get('id') != $filterParams['pass_release']['value']
            || $event->getItem()->isVariant()
        ) {
            return;
        }

        $parsed              = $event->getResult();
        $parsed['actions'][] = [
            'link'      => $GLOBALS['TL_LANG']['MSC']['copyLink'][0],
            'title'     => $GLOBALS['TL_LANG']['MSC']['copyLink'][1],
            'class'     => 'copy',
            'href'      => str_replace('act=edit', 'act=copy', $parsed['editUrl']),
            'attribute' => '',
        ];

        $event->setResult($parsed);
    }


    /**
     * Add the create variant link to the host list
     *
     * @param ParseItemEvent $event
     */
    public function addCreateVariantLink(ParseItemEvent $event)
    {
        if ('mm_ferienpass' !== $event->getItem()->getMetaModel()->getTableName()) {
            return;
        }

        global $container;

        $settings = $event->getRenderSettings();
        if (!$settings->get(self::FILTER_PARAMS_FLAG)) {
            return;
        }
        $filterParams = $settings->get(self::FILTER_PARAMS_FLAG);

        /** @var Item $passRelease */
        $passRelease = $container['ferienpass.pass-release.edit-current'];
        if (null === $passRelease) {
            // TODO overthink error handling
            return;
        }

        if (!($passRelease->get('id') == $filterParams['pass_release']['value']
              && $event->getItem()->isVariantBase()
              && $event->getItem()->getVariants(null)->getCount())
        ) {
            return;
        }

        $parsed              = $event->getResult();
        $parsed['actions'][] = [
            'link'      => $GLOBALS['TL_LANG']['MSC']['createVariantLink'][0],
            'title'     => $GLOBALS['TL_LANG']['MSC']['createVariantLink'][1],
            'class'     => 'createVariant',
            'href'      => str_replace(
                ['act=edit', 'id'],
                ['act=create', 'vargroup'],
                $parsed['editUrl']
            ),
            'attribute' => '',
        ];

        $event->setResult($parsed);
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
