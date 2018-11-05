<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\EventListener\HostEditing;


use Contao\CoreBundle\Routing\UrlGenerator;
use Contao\Template;
use MetaModels\Events\ParseItemEvent;
use MetaModels\Events\RenderItemListEvent;
use MetaModels\IItem;
use MetaModels\ViewCombination\ViewCombination;
use Richardhj\ContaoFerienpassBundle\Entity\PassEdition;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\Exception\InvalidArgumentException;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class ItemListRenderingListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\HostEditing
 */
class ItemListRenderingListener
{

    /**
     * The translator.
     *
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * The current view combination.
     *
     * @var ViewCombination
     */
    private $viewCombination;

    /**
     * Doctrine.
     *
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * The url generator.
     *
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * ItemListRenderingListener constructor.
     *
     * @param TranslatorInterface      $translator      The translator.
     * @param ViewCombination          $viewCombination The current view combinations.
     * @param ManagerRegistry          $doctrine        Doctrine.
     * @param UrlGenerator             $urlGenerator The url generator.
     */
    public function __construct(
        TranslatorInterface $translator,
        ViewCombination $viewCombination,
        ManagerRegistry $doctrine,
        UrlGenerator $urlGenerator
    ) {
        $this->translator      = $translator;
        $this->viewCombination = $viewCombination;
        $this->doctrine        = $doctrine;
        $this->urlGenerator    = $urlGenerator;
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

        $passEditionId = \Input::get('pass_edition');
        if (null === $passEditionId) {
            $passEdition = $this->doctrine->getRepository(PassEdition::class)->findDefaultPassEditionForHost();
            if ($passEdition instanceof PassEdition) {
                $passEditionId = $passEdition->getId();
            }
        }

        if ($passEditionId) {
            $editable = $this->passEditionIsInHostEditingStage($passEditionId);

            $event->getTemplate()->editEnable = $editable;
            if ($template = $caller instanceof Template ? $caller : $caller->Template) {
                $template->editEnable = $editable;
            }
        }
    }

    /**
     * Add the application list jumpTo as action.
     *
     * @param ParseItemEvent $event The event.
     *
     * @return void
     */
    public function addApplicationListLink(ParseItemEvent $event): void
    {
        $screen    = $this->viewCombination->getScreen('mm_ferienpass');
        $metaModel = $event->getItem()->getMetaModel();
        $tableName = $metaModel->getTableName();

        if ('mm_ferienpass' !== $tableName
            || '3' !== $screen['meta']['id']
            || !$event->getItem()->get('applicationlist_active')
        ) {
            return;
        }

        $parsed = $event->getResult();

        $parsed['actions']['applicationlist'] = [
            'label' => $this->translateLabel('metamodel_show_application_list.0', $metaModel->getTableName()),
            'title' => $this->translateLabel('metamodel_show_application_list.1', $metaModel->getTableName()),
            'class' => 'applicationlist',
            'href'  => $this->urlGenerator->generate(
                'angebote-verwalten/teilnehmerliste',
                ['auto_item' => $parsed['raw']['alias']]
            ),
        ];

        $event->setResult($parsed);
    }

    /**
     * Disable the actions when editing is not allowed.
     *
     * @param ParseItemEvent $event The event.
     *
     * @return void
     */
    public function modifyActionButtons(ParseItemEvent $event): void
    {
        $screen    = $this->viewCombination->getScreen('mm_ferienpass');
        $item      = $event->getItem();
        $metaModel = $item->getMetaModel();

        if ('3' !== $screen['meta']['id'] || 'mm_ferienpass' !== $metaModel->getTableName()) {
            return;
        }

        $result = $event->getResult();

        // Set buttons disabled if over.
        if (false === $this->offerIsEditableForHost($item)) {
            foreach (['edit', 'delete', 'createvariant'] as $action) {
                if (isset($result['actions'][$action])) {
                    $result['actions'][$action]['class'] .= ' btn--disabled';
                }
            }
        }

        // Disable copy action if no host editing stage.
        if (null === $this->doctrine->getManager()->getRepository(PassEdition::class)->findOneToEdit()) {
            $result['actions']['copy']['class'] .= ' btn--disabled';
        }

        // Remove copy action for variants.
        if ($item->isVariant()) {
            unset($result['actions']['copy']);
        }

        $event->setResult($result);
    }

    /**
     * Check whether one offer is editable for the host by checking the edit deadline.
     *
     * @param IItem $offer The offer.
     *
     * @return bool
     */
    private function offerIsEditableForHost(IItem $offer): bool
    {
        $passEdition = $offer->get('pass_edition');
        if (null === $passEdition) {
            throw new \LogicException('Please add "pass_edition" to the active render setting.');
        }

        return $this->passEditionIsInHostEditingStage($passEdition['id']);
    }

    /**
     * Check whether one pass edition is in the host editing stage and editable.
     *
     * @param int $passEditionId The pass edition id.
     *
     * @return bool
     */
    private function passEditionIsInHostEditingStage(int $passEditionId): bool
    {
        $passEdition      = $this->doctrine->getRepository(PassEdition::class)->find($passEditionId);
        $hostEditingStage = $passEdition->getCurrentHostEditingStage();

        return null !== $hostEditingStage;
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
