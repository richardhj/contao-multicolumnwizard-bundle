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
use Richardhj\ContaoFerienpassBundle\Util\PassReleases;
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
     * @var PassReleases
     */
    private $passReleases;

    /**
     * ItemListRenderingListener constructor.
     *
     * @param EventDispatcherInterface $dispatcher The event dispatcher.
     * @param TranslatorInterface $translator The translator.
     * @param ViewCombination $viewCombination The current view combinations.
     * @param PassReleases $passReleases
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        TranslatorInterface $translator,
        ViewCombination $viewCombination,
        PassReleases $passReleases
    )
    {
        $this->dispatcher = $dispatcher;
        $this->translator = $translator;
        $this->viewCombination = $viewCombination;
        $this->passReleases = $passReleases;
    }

    /**
     * Set the jumpTo for later purpose.
     *
     * @param RenderItemListEvent $event The event.
     *
     * @return void
     */
    public function handleRenderItemList(RenderItemListEvent $event): void
    {
        $caller = $event->getCaller();
        if (null === $caller || 'mm_ferienpass' !== $event->getList()->getMetaModel()->getTableName()) {
            return;
        }

        $event->getList()->getView()->set('$jump-to-application-list', $caller->jumpTo_application_list);
    }

    /**
     * Add the application list jumpTo as action.
     *
     * @param ParseItemEvent $event
     *
     * @return void
     */
    public function addApplicationListLink(ParseItemEvent $event): void
    {
        $screen = $this->viewCombination->getScreen('mm_ferienpass');
        $settings = $event->getRenderSettings();
        $jumpTo = PageModel::findByPk($settings->get('$jump-to-application-list'));
        $item = $event->getItem();
        $metaModel = $item->getMetaModel();
        $tableName = $metaModel->getTableName();

        if ('mm_ferienpass' !== $tableName
            || null === $jumpTo
            || '3' !== $screen['meta']['id']
            || !$event->getItem()->get('applicationlist_active')
        ) {
            return;
        }

        $parsed = $event->getResult();

        $generateFrontendUrlEvent = new GenerateFrontendUrlEvent($jumpTo->row(), '/' . $parsed['raw']['alias']);
        $this->dispatcher->dispatch(
            ContaoEvents::CONTROLLER_GENERATE_FRONTEND_URL,
            $generateFrontendUrlEvent
        );

        $parsed['actions']['applicationlist'] = [
            'label' => $this->translateLabel('metamodel_show_application_list.0', $metaModel->getTableName()),
            'title' => $this->translateLabel('metamodel_show_application_list.1', $metaModel->getTableName()),
            'class' => 'applicationlist',
            'href' => $generateFrontendUrlEvent->getUrl(),
        ];

        $event->setResult($parsed);
    }

    /**
     * Disable the actions when editing is not allowed.
     *
     * @param ParseItemEvent $event
     *
     * @return void
     */
    public function modifyActionButtons(ParseItemEvent $event): void
    {
        $screen = $this->viewCombination->getScreen('mm_ferienpass');
        $item = $event->getItem();
        $metaModel = $item->getMetaModel();

        if ('3' !== $screen['meta']['id'] || 'mm_ferienpass' !== $metaModel->getTableName()) {
            return;
        }

        $result = $event->getResult();

        // Set buttons disabled if over.
        if (false === self::offerIsEditableForHost($item)) {
            foreach (['edit', 'delete', 'createvariant'] as $action) {
                if (isset($result['actions'][$action])) {
                    $result['actions'][$action]['class'] .= ' disabled';
                }
            }
        }

        // Add attribute.
        if (isset($result['actions']['jumpTo'])) {
            $result['actions']['jumpTo']['attribute'] = 'data-lightbox';
        }

        // Remove copy action for variants.
        if ($item->isVariant()) {
            unset($result['actions']['copy']);
        }

        // Remove copy action for items currently being allowed to edit.
        if (true === self::offerIsEditableForHost($item) && null !== $this->passReleases->getPassReleaseToEdit()) {
            unset($result['actions']['copy']);
        }

        $event->setResult($result);
    }

    /**
     * Check whether one offer is editable for the host by checking the edit deadline
     *
     * @param IItem $offer
     *
     * @return bool
     */
    private static function offerIsEditableForHost(IItem $offer): ?bool
    {
        $end = self::getHostEditEnd($offer);
        if (null === $end) {
            return null;
        }

        return (time() <= $end);
    }

    /**
     * Get the host edit deadline for a pacific offer
     *
     * @param IItem $offer
     *
     * @return int The host editing end as unix timestamp.
     */
    private static function getHostEditEnd(IItem $offer): ?int
    {
        $passRelease = $offer->get('pass_release');
        if (null === $passRelease) {
            return null;
        }

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
     * @param string $transString The non translated label for the button.
     *
     * @param string $definitionName The data definition of the current item.
     *
     * @param array $parameters The parameters to pass to the translator.
     *
     * @return string
     */
    private function translateLabel($transString, $definitionName, array $parameters = []): string
    {
        $translator = $this->translator;
        try {
            $label = $translator->trans($definitionName . '.' . $transString, $parameters, 'contao_' . $definitionName);
            if ($label !== $definitionName . '.' . $transString) {
                return $label;
            }
        } catch (InvalidArgumentException $e) {
            // Ok. Next try.
        }

        try {
            $label = $translator->trans('MSC.' . $transString, $parameters, 'contao_default');
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
