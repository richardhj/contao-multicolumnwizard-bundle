<?php
/**
 * E-POSTBUSINESS API integration for Contao Open Source CMS
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package E-POST
 * @author  Richard Henkenjohann <richard-epost@henkenjohann.me>
 */

namespace Ferienpass\DcGeneral\View;


use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\Controller\RedirectEvent;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\LogEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\ParentView;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\ViewHelpers;
use Ferienpass\Model\AttendanceStatus;


class OfferAttendancesView extends ParentView
{

    public function viewParent($collection, $parentModel)
    {
        $definition = $this->getEnvironment()->getDataDefinition();
        $parentProvider = $definition->getBasicDefinition()->getParentDataProvider();
        $groupingInformation = ViewHelpers::getGroupingMode($this->environment);
        $dispatcher = $this->getEnvironment()->getEventDispatcher();

        // Skip if we have no parent or parent collection.
        if (!$parentModel) {
            $dispatcher->dispatch(
                ContaoEvents::SYSTEM_LOG,
                new LogEvent(
                    sprintf(
                        'The view for %s has either a empty parent data provider or collection.',
                        $parentProvider
                    ),
                    __CLASS__.'::'.__FUNCTION__.'()',
                    TL_ERROR
                )
            );

            $dispatcher->dispatch(
                ContaoEvents::CONTROLLER_REDIRECT,
                new RedirectEvent('contao/main.php?act=error')
            );
        }

        $objTemplate = $this->getTemplate('dcbe_general_offerAttendancesView');

        $this
            ->addToTemplate('status', AttendanceStatus::findBy('enableManualSort', 1), $objTemplate)
            ->addToTemplate('tableName', strlen($definition->getName()) ? $definition->getName() : 'none', $objTemplate)
            ->addToTemplate('collection', $collection, $objTemplate)
//            ->addToTemplate('select', $this->isSelectModeActive(), $objTemplate)
            ->addToTemplate('action', ampersand(\Environment::get('request'), true), $objTemplate)
            ->addToTemplate('header', $this->renderFormattedHeaderFields($parentModel), $objTemplate)
            ->addToTemplate('mode', ($groupingInformation ? $groupingInformation['mode'] : null), $objTemplate)
            ->addToTemplate('pdp', (string)$parentProvider, $objTemplate)
            ->addToTemplate('cdp', $definition->getName(), $objTemplate)
//            ->addToTemplate('selectButtons', $this->getSelectButtons(), $objTemplate)
            ->addToTemplate('headerButtons', $this->getHeaderButtons($parentModel), $objTemplate)
            ->addToTemplate('sortable', (bool)ViewHelpers::getManualSortingProperty($this->environment), $objTemplate)
            ->addToTemplate('showColumns', $this->getViewSection()->getListingConfig()->getShowColumns(), $objTemplate);

        $this->renderEntries($collection, $groupingInformation);

        // Add breadcrumb, if we have one.
        $strBreadcrumb = $this->breadcrumb();
        if ($strBreadcrumb != null) {
            $this->addToTemplate('breadcrumb', $strBreadcrumb, $objTemplate);
        }

        return $objTemplate->parse();
    }
}
