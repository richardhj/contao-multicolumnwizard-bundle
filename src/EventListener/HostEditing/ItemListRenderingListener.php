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
use MetaModels\ContaoFrontendEditingBundle\EventListener\RenderItemListListener;
use MetaModels\Events\ParseItemEvent;
use MetaModels\Events\RenderItemListEvent;
use MetaModels\FrontendIntegration\HybridList;
use MetaModels\IItem;
use MetaModels\MetaModelsEvents;
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
     * The event dispatcher.
     *
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * ItemListRenderingListener constructor.
     *
     * @param TranslatorInterface      $translator   The translator.
     * @param ManagerRegistry          $doctrine     Doctrine.
     * @param UrlGenerator             $urlGenerator The url generator.
     * @param EventDispatcherInterface $dispatcher   The event dispatcher.
     */
    public function __construct(
        TranslatorInterface $translator,
        ManagerRegistry $doctrine,
        UrlGenerator $urlGenerator,
        EventDispatcherInterface $dispatcher
    ) {
        $this->translator   = $translator;
        $this->doctrine     = $doctrine;
        $this->urlGenerator = $urlGenerator;
        $this->dispatcher   = $dispatcher;
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
        $caller    = $event->getCaller();
        $metaModel = $event->getList()->getMetaModel();
        if (!($caller instanceof HybridList) || 'mm_ferienpass' !== $metaModel->getTableName()) {
            return;
        }

        $isEditableFlag = $event->getList()->getView()->get(RenderItemListListener::FRONTEND_EDITING_ENABLED_FLAG);
        if (true !== $isEditableFlag) {
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
            $editable = $this->passEditionIsEditableForHosts($passEditionId);

            $event->getTemplate()->editEnable = $editable;
            if ($template = $caller instanceof Template ? $caller : $caller->Template) {
                $template->editEnable = $editable;
            }

            $this->dispatcher->addListener(MetaModelsEvents::PARSE_ITEM, [$this, 'addApplicationListLink']);
            $this->dispatcher->addListener(MetaModelsEvents::PARSE_ITEM, [$this, 'modifyActionButtons']);
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
        $metaModel = $event->getItem()->getMetaModel();
        $tableName = $metaModel->getTableName();

        if ('mm_ferienpass' !== $tableName || !$event->getItem()->get('applicationlist_active')) {
            return;
        }

        $parsed = $event->getResult();

        $parsed['actions']['applicationlist'] = [
            'label' => $this->translateLabel('metamodel_show_application_list.0', $metaModel->getTableName()),
            'title' => $this->translateLabel('metamodel_show_application_list.1', $metaModel->getTableName()),
            'class' => 'applicationlist',
            'href'  => $this->urlGenerator->generate(
                'angebote-verwalten/teilnehmerliste/{item}',
                ['item' => $parsed['raw']['alias'], 'auto_item' => 'item']
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
        $item      = $event->getItem();
        $metaModel = $item->getMetaModel();

        if ('mm_ferienpass' !== $metaModel->getTableName()) {
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

        $currentPassEdition = $this->doctrine->getManager()->getRepository(PassEdition::class)->findOneToEdit();

        // Remove copy action if no host editing stage, for variants and for currently editable offers.
        if (null === $currentPassEdition
            || $item->isVariant()
            || (int) $item->get('pass_edition')['id'] === $currentPassEdition->getId()) {
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

        return $this->passEditionIsEditableForHosts($passEdition['id']);
    }

    /**
     * Check whether one pass edition is in the host editing stage and editable.
     *
     * @param int $passEditionId The pass edition id.
     *
     * @return bool
     */
    private function passEditionIsEditableForHosts(int $passEditionId): bool
    {
        /** @var PassEdition $passEdition */
        $passEdition = $this->doctrine->getRepository(PassEdition::class)->find($passEditionId);

        return $passEdition->isEditableForHosts();
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
