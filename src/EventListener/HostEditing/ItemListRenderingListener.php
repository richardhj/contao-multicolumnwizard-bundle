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

namespace Richardhj\ContaoFerienpassBundle\EventListener\HostEditing;


use Contao\PageModel;
use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\GenerateFrontendUrlEvent;
use MetaModels\AttributeSelectBundle\Attribute\MetaModelSelect;
use MetaModels\Events\ParseItemEvent;
use MetaModels\Events\RenderItemListEvent;
use MetaModels\IItem;
use MetaModels\ViewCombination\ViewCombination;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\TranslatorInterface;

class ItemListRenderingListener
{

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ViewCombination
     */
    private $viewCombination;

    /**
     * Set the jumpTo for later purpose.
     *
     * @param RenderItemListEvent $event The event.
     */
    public function handleRenderItemList(RenderItemListEvent $event)
    {
        $caller = $event->getCaller();
        if ('mm_ferienpass' !== $event->getList()->getMetaModel()->getTableName()) {
            return;
        }

        $event->getList()->getView()->set('$jump-to-application-list', $caller->jumpTo_application_list);
    }

    /**
     * Add the application list jumpTo as action.
     *
     * @param ParseItemEvent $event
     */
    public function addApplicationListLink(ParseItemEvent $event)
    {
        $screen    = $this->viewCombination->getScreen('mm_ferienpass');
        $settings  = $event->getRenderSettings();
        $jumpTo    = PageModel::findByPk($settings->get('$jump-to-application-list'));
        $item      = $event->getItem();
        $metaModel = $item->getMetaModel();
        $tableName = $metaModel->getTableName();

        if ('mm_ferienpass' !== $tableName
            || null === $jumpTo
            || '3' !== $screen['meta']['id']
            || !$event->getItem()->get('applicationlist_active')
            || ($event->getItem()->isVariantBase() && $event->getItem()->getVariants(null)->getCount())
        ) {
            return;
        }

        $parsed = $event->getResult();

        $generateFrontendUrlEvent = new GenerateFrontendUrlEvent($jumpTo->row(), '/'.$parsed['raw']['alias']);
        $this->dispatcher->dispatch(
            ContaoEvents::CONTROLLER_GENERATE_FRONTEND_URL,
            $generateFrontendUrlEvent
        );

        $parsed['actions']['applicationlist'] = [
            'label' => $this->translateLabel('metamodel_show_application_list.0', $metaModel->getTableName()),
            'title' => $this->translateLabel('metamodel_show_application_list.1', $metaModel->getTableName()),
            'class' => 'applicationlist',
            'href'  => $generateFrontendUrlEvent->getUrl(),
        ];

        $event->setResult($parsed);
    }

    /**
     * Disable the actions when editing is not allowed.
     *
     * @param ParseItemEvent $event
     */
    public function modifyActionButtons(ParseItemEvent $event)
    {
        $screen    = $this->viewCombination->getScreen('mm_ferienpass');
        $item      = $event->getItem();
        $metaModel = $item->getMetaModel();

        if ('3' !== $screen['meta']['id'] || 'mm_ferienpass' !== $metaModel->getTableName()) {
            return;
        }

        $result = $event->getResult();

        if (false === self::offerIsEditableForHost($event->getItem())) {
            foreach (['edit', 'delete', 'copy', 'createvariant'] as $action) {
                if (isset($result['actions'][$action])) {
                    // Add css class.
                    $result['actions'][$action]['class'] .= ' disabled';
                }
            }
        }

        // Add attribute.
        $parsed['actions']['jumpTo']['attribute'] = 'data-lightbox';

        $event->setResult($result);
    }

    /**
     * Check whether one offer is editable for the host by checking the edit deadline
     *
     * @param IItem $offer
     *
     * @return bool
     */
    private static function offerIsEditableForHost(IItem $offer): bool
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

        return $passRelease[MetaModelSelect::SELECT_RAW]['host_edit_end'];
    }

    /**
     * Get a translated label from the translator.
     *
     * The fallback is as follows:
     * 1. Try to translate via the data definition name as translation section.
     * 2. Try to translate with the prefix 'MSC.'.
     * 3. Return the input value as nothing worked out.
     *
     * @param string $transString    The non translated label for the button.
     *
     * @param string $definitionName The data definition of the current item.
     *
     * @param array  $parameters     The parameters to pass to the translator.
     *
     * @return string
     */
    private function translateLabel($transString, $definitionName, array $parameters = [])
    {
        $translator = $this->translator;
        try {
            $label = $translator->trans($definitionName.'.'.$transString, $parameters, 'contao_'.$definitionName);
            if ($label !== $definitionName.'.'.$transString) {
                return $label;
            }
        } catch (InvalidArgumentException $e) {
            // Ok. Next try.
        }

        try {
            $label = $translator->trans('MSC.'.$transString, $parameters, 'contao_default');
            if ($label !== $transString) {
                return $label;
            }
        } catch (InvalidArgumentException $e) {
            // Ok. Next try.
        }

        // Fallback, just return the key as is it.
        return $transString;
    }
}
