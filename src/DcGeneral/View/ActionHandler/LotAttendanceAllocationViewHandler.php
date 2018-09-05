<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\DcGeneral\View\ActionHandler;

use ContaoCommunityAlliance\DcGeneral\Action;
use ContaoCommunityAlliance\DcGeneral\Contao\DataDefinition\Definition\Contao2BackendViewDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\ActionHandler\ParentedListViewShowAllHandler;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\ContaoBackendViewTemplate;
use ContaoCommunityAlliance\DcGeneral\EnvironmentInterface;
use ContaoCommunityAlliance\Translator\TranslatorInterface as CcaTranslator;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\Lot;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;
use Richardhj\ContaoFerienpassBundle\Model\Offer as OfferModel;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class LotAttendanceAllocationViewHandler
 *
 * @package Richardhj\ContaoFerienpassBundle\DcGeneral\View\ActionHandler
 */
class LotAttendanceAllocationViewHandler extends ParentedListViewShowAllHandler
{

    /**
     * The offer model.
     *
     * @var OfferModel
     */
    private $offerModel;

    /**
     * AbstractHandler constructor.
     *
     * @param RequestScopeDeterminator $scopeDeterminator The request mode determinator.
     * @param TranslatorInterface      $translator        The translator.
     * @param CcaTranslator            $ccaTranslator     The cca translator.
     * @param OfferModel               $offerModel        The offer model.
     */
    public function __construct(
        RequestScopeDeterminator $scopeDeterminator,
        TranslatorInterface $translator,
        CcaTranslator $ccaTranslator,
        OfferModel $offerModel
    ) {
        parent::__construct($scopeDeterminator, $translator, $ccaTranslator);

        $this->offerModel = $offerModel;
    }

    /**
     * Process the action.
     *
     * @param Action               $action      The action being handled.
     * @param EnvironmentInterface $environment Current dc-general environment.
     *
     * @return string
     */
    protected function process(Action $action, EnvironmentInterface $environment): ?string
    {
        $dataDefinition  = $environment->getDataDefinition();
        $basicDefinition = $environment->getDataDefinition()->getBasicDefinition();
        if ('mm_ferienpass' !== $basicDefinition->getParentDataProvider()) {
            // Set null and the next view handler will handle it.
            return null;
        }

        $parentModel = $this->loadParentModel($environment);
        $offer       = $this->offerModel->findById($parentModel->getId());
        if (null === $offer) {
            return null;
        }

        $applicationSystem = $this->offerModel->getApplicationSystem($offer);
        if (!($applicationSystem instanceof Lot)) {
            return null;
        }

        // Add "attendances" property
        /** @var Contao2BackendViewDefinitionInterface $viewSection */
        $viewSection = $dataDefinition->getDefinition(Contao2BackendViewDefinitionInterface::NAME);
        $listing     = $viewSection->getListingConfig();
        $formatter   = $listing->getLabelFormatter($dataDefinition->getName());

        $propertyNames   = $formatter->getPropertyNames();
        $propertyNames[] = 'attendances';
        $formatter->setPropertyNames($propertyNames);

        return parent::process($action, $environment);
    }

    /**
     * Prepare the template.
     *
     * @param EnvironmentInterface      $environment The environment.
     * @param ContaoBackendViewTemplate $template    The template to populate.
     *
     * @return void
     */
    protected function renderTemplate(ContaoBackendViewTemplate $template, EnvironmentInterface $environment): void
    {
        parent::renderTemplate($template, $environment);

        /** @var AttendanceStatus|\Model\Collection $status */
        $status      = AttendanceStatus::findAll();
        $statusCount = [];

        $parentModel = $this->loadParentModel($environment);

        while (null !== $status && $status->next()) {
            $statusCount[$status->id]['current'] = Attendance::countByOfferAndStatus(
                $parentModel->getProperty('id'),
                $status->id
            );

            $statusCount[$status->id]['max'] = (AttendanceStatus::findConfirmed() === $status)
                ? $parentModel->getProperty('applicationlist_max') : '-';
        }

        $template->set('status', $status);
        $template->set('statusCount', $statusCount);
    }

    /**
     * Determine the template to use.
     *
     * @param array $groupingInformation The grouping information as retrieved via ViewHelpers::getGroupingMode().
     *
     * @return ContaoBackendViewTemplate
     */
    protected function determineTemplate($groupingInformation): ContaoBackendViewTemplate
    {
        return $this->getTemplate('dcbe_general_attendance_allocation_view');
    }
}
