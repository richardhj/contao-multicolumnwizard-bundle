<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

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
use ContaoCommunityAlliance\DcGeneral\Data\CollectionInterface;
use ContaoCommunityAlliance\DcGeneral\Data\ModelInterface;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\AttendanceStatus;
use Ferienpass\Model\Config as FerienpassConfig;


/**
 * Class OfferAttendancesView
 * @package Ferienpass\DcGeneral\View
 */
class OfferAttendancesView extends ParentView
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

        $status = AttendanceStatus::findBy('enableManualAssignment', 1);
        $statusCount = [];

        while (null !== $status && $status->next()) {
            $statusCount[$status->id]['current'] = Attendance::countByOfferAndStatus(
                $parentModel->getProperty('id'),
                $status->id
            );
            $statusCount[$status->id]['max'] = ($status->id === AttendanceStatus::findConfirmed()->id)
                ? $parentModel->getProperty(
                    FerienpassConfig::getInstance()->offer_attribute_applicationlist_max
                ) : '-';
        }

        $this
            ->addToTemplate('status', $status, $objTemplate)
            ->addToTemplate('statusCount', $statusCount, $objTemplate)
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
