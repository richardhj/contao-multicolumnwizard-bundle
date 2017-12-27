<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\DcGeneral\View;

use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\RedirectEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\LogEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\ParentView;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\ViewHelpers;
use ContaoCommunityAlliance\DcGeneral\Data\CollectionInterface;
use ContaoCommunityAlliance\DcGeneral\Data\ModelInterface;
use Environment;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;


/**
 * Class AttendanceAllocationView
 *
 * @package Richardhj\ContaoFerienpassBundle\DcGeneral\View
 */
class AttendanceAllocationView extends ParentView
{

    /**
     * Show parent view mode 4.
     *
     * @param CollectionInterface $collection  The collection containing the models.
     *
     * @param ModelInterface      $parentModel The parent model.
     *
     * @return string HTML output
     */
    public function viewParent($collection, $parentModel)
    {
        $definition          = $this->getEnvironment()->getDataDefinition();
        $parentProvider      = $definition->getBasicDefinition()->getParentDataProvider();
        $groupingInformation = ViewHelpers::getGroupingMode($this->environment);
        $dispatcher          = $this->getEnvironment()->getEventDispatcher();

        // Skip if we have no parent or parent collection.
        if (!$parentModel) {
            $dispatcher->dispatch(
                ContaoEvents::SYSTEM_LOG,
                new LogEvent(
                    sprintf(
                        'The view for %s has either a empty parent data provider or collection.',
                        $parentProvider
                    ),
                    __METHOD__,
                    TL_ERROR
                )
            );

            $dispatcher->dispatch(
                ContaoEvents::CONTROLLER_REDIRECT,
                new RedirectEvent('contao/main.php?act=error')
            );
        }

        $template = $this->getTemplate('dcbe_general_attendance_allocation_view');

        /** @var AttendanceStatus|\Model\Collection $status */
        $status      = AttendanceStatus::findBy('enableManualAssignment', 1);
        $statusCount = [];

        while (null !== $status && $status->next()) {
            $statusCount[$status->id]['current'] = Attendance::countByOfferAndStatus(
                $parentModel->getProperty('id'),
                $status->id
            );
            $statusCount[$status->id]['max']     = (AttendanceStatus::findConfirmed() === $status)
                ? $parentModel->getProperty('applicationlist_max') : '-';
        }

        $this
            ->addToTemplate('status', $status, $template)
            ->addToTemplate('statusCount', $statusCount, $template)
            ->addToTemplate('tableName', strlen($definition->getName()) ? $definition->getName() : 'none', $template)
            ->addToTemplate('collection', $collection, $template)
            ->addToTemplate('action', ampersand(Environment::get('request'), true), $template)
            ->addToTemplate('header', $this->renderFormattedHeaderFields($parentModel), $template)
            ->addToTemplate('mode', ($groupingInformation ? $groupingInformation['mode'] : null), $template)
            ->addToTemplate('pdp', (string) $parentProvider, $template)
            ->addToTemplate('cdp', $definition->getName(), $template)
            ->addToTemplate('headerButtons', $this->getHeaderButtons($parentModel), $template)
            ->addToTemplate('sortable', (bool) ViewHelpers::getManualSortingProperty($this->environment), $template)
            ->addToTemplate('showColumns', $this->getViewSection()->getListingConfig()->getShowColumns(), $template);
//            ->addToTemplate('select', $this->isSelectModeActive(), $objTemplate)
//            ->addToTemplate('selectButtons', $this->getSelectButtons(), $objTemplate)

        $this->renderEntries($collection, $groupingInformation);

        // Add breadcrumb, if we have one.
        $breadcrumb = $this->breadcrumb();
        if (null !== $breadcrumb) {
            $this->addToTemplate('breadcrumb', $breadcrumb, $template);
        }

        return $template->parse();
    }
}
