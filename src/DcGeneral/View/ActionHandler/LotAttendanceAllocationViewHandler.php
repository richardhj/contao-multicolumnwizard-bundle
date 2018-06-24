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
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\ApplicationSystemInterface;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\Lot;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;
use Symfony\Component\Translation\TranslatorInterface;


class LotAttendanceAllocationViewHandler extends ParentedListViewShowAllHandler
{

    /**
     * @var ApplicationSystemInterface
     */
    private $applicationSystem;

    /**
     * AbstractHandler constructor.
     *
     * @param RequestScopeDeterminator   $scopeDeterminator The request mode determinator.
     * @param TranslatorInterface        $translator        The translator.
     * @param CcaTranslator              $ccaTranslator     The cca translator.
     * @param ApplicationSystemInterface $applicationSystem
     */
    public function __construct(
        RequestScopeDeterminator $scopeDeterminator,
        TranslatorInterface $translator,
        CcaTranslator $ccaTranslator,
        ApplicationSystemInterface $applicationSystem
    ) {
        parent::__construct($scopeDeterminator, $translator, $ccaTranslator);

        $this->applicationSystem = $applicationSystem;
    }

    /**
     * Check if the action should be handled.
     *
     * @param string $mode   The list mode.
     * @param Action $action The action.
     *
     * @return bool
     */
    protected function wantToHandle($mode, Action $action): bool
    {
        return ($this->applicationSystem instanceof Lot) && parent::wantToHandle($mode, $action);
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
//        $backendView     = $dataDefinition->getDefinition(Contao2BackendViewDefinitionInterface::NAME);
//
//        $backendView->getListingConfig()->setShowColumns(false);
//
//        $basicDefinition->setMode(BasicDefinitionInterface::MODE_FLAT);

        if ('mm_ferienpass' !== $basicDefinition->getParentDataProvider()) {
            // Set null and the next view handler will handle it.
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
//        $inputProvider = $environment->getInputProvider();
//
//        $languageDomain  = 'contao_' . $environment->getDataDefinition()->getName();
//
//        $this->getViewSection($environment->getDataDefinition())->getListingConfig()->setShowColumns(false);

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

//        $template->set(
//            'subHeadline',
//            \sprintf(
//                '%s: %s',
//                $this->translate('MSC.' . $inputProvider->getParameter('mode') . 'Selected', $languageDomain),
//                $this->translate('MSC.edit_all_select_properties', $languageDomain)
//            )
//        );
//        $template->set('mode', 'none');
//        $template->set('floatRightSelectButtons', true);
//        $template->set('selectCheckBoxName', 'properties[]');
//        $template->set('selectCheckBoxIdPrefix', 'properties_');
//
//        if ((null !== $template->get('action'))
//            && (false !== \strpos($template->get('action'), 'select=properties'))
//        ) {
//            $template->set('action', \str_replace('select=properties', 'select=edit', $template->get('action')));
//        }
//
//        if (\count($this->messages) > 0) {
//            foreach (\array_keys($this->messages) as $messageType) {
//                $template->set($messageType, $this->messages[$messageType]);
//            }
//        }
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
