<?php
/**
 * E-POSTBUSINESS API integration for Contao Open Source CMS
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package E-POST
 * @author  Richard Henkenjohann <richard-epost@henkenjohann.me>
 */

namespace Ferienpass\ApplicationSystem;


use ContaoCommunityAlliance\DcGeneral\Contao\Dca\Populator\DataProviderPopulator;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ModelToLabelEvent;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\PopulateEnvironmentEvent;
use Ferienpass\DcGeneral\View\OfferAttendancesView;
use Ferienpass\Event\SaveAttendanceEvent;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\AttendanceStatus;
use Ferienpass\Model\Config as FerienpassConfig;


class Lot extends AbstractApplicationSystem
{

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SaveAttendanceEvent::NAME      => [
                'updateAttendanceStatus',
            ],
            PopulateEnvironmentEvent::NAME => [
                ['populateEnvironmentForAttendancesChildTable', DataProviderPopulator::PRIORITY * 1.5],
            ],
            ModelToLabelEvent::NAME        => [
                ['addMemberEditLinkForParticipantListView', -10],
            ],
        ];
    }


    /**
     * Save the "waiting" status for one attendance per default
     *
     * @param SaveAttendanceEvent $event
     */
    public function updateAttendanceStatus(SaveAttendanceEvent $event)
    {
        $attendance = $event->getAttendance();

        if (null !== $attendance->getStatus()) {
            return;
        }

        $attendance->status = AttendanceStatus::findWaiting()->id;
        $attendance->save();
    }


    public function populateEnvironmentForAttendancesChildTable(PopulateEnvironmentEvent $event)
    {
        $environment = $event->getEnvironment();

        // Already populated or not in Backend? Get out then.
        if ($environment->getView() || ('BE' !== TL_MODE)) {
            return;
        }

        $definition = $environment->getDataDefinition();

        // Not attendances for offer MetaModel
        if (!($definition->getName() === Attendance::getTable()
                && $definition
                    ->getBasicDefinition()
                    ->getParentDataProvider() === FerienpassConfig::getInstance()->offer_model)
            || !$definition->hasBasicDefinition()
        ) {
            return;
        }

        $view = new OfferAttendancesView();

        $view->setEnvironment($environment);
        $environment->setView($view);


//        /** @var Contao2BackendViewDefinitionInterface $viewDefinition */
//        $viewDefinition = $definition->getDefinition(Contao2BackendViewDefinitionInterface::NAME);
//
//        $listingConfig = $viewDefinition->getListingConfig();
//
//        $listingConfig->setShowColumns(false);
    }


    public function addMemberEditLinkForParticipantListView(ModelToLabelEvent $event)
    {
        $model = $event->getModel();
        $definition = $event->getEnvironment()->getDataDefinition();

        // Not attendances for offer MetaModel
        if (!($definition->getName() === Attendance::getTable()
                && $definition
                    ->getBasicDefinition()
                    ->getParentDataProvider() === FerienpassConfig::getInstance()->offer_model)
            || !$definition->hasBasicDefinition()
        ) {
            return;
        }

        $args = $event->getArgs();
//
//        \System::loadLanguageFile('tl_member');
//
//        $parentRaw = $model->getItem()->get($parentColName);

//        unset($args['offer']);
//
        // Adjust the label
        foreach ($args as $k => $v) {
            switch ($k) {
//                case $parentColName:
//                    /** @noinspection HtmlUnknownTarget */
//                    $args[$k] = sprintf(
//                        '<a href="contao/main.php?do=member&amp;act=edit&amp;id=%1$u&amp;popup=1&amp;nb=1&amp;rt=%4$s" class="open_parent" title="%3$s" onclick="Backend.openModalIframe({\'width\':768,\'title\':\'%3$s\',\'url\':this.href});return false">%2$s</a>',
//                        // Member ID
//                        $parentRaw['id'],
//                        // Link
//                        '<i class="fa fa-external-link tl_gray"></i> '.$args[$k],
//                        // Member edit description
//                        sprintf(
//                            $GLOBALS['TL_LANG']['tl_member']['edit'][1],
//                            $parentRaw['id']
//                        ),
//                        REQUEST_TOKEN
//                    );
//                    break;

//                default:
//                    if ('' === $model->getItem()->get($k) && '' !== ($parentData = $parentRaw[$k])) {
//                        $args[$k] = sprintf('<span class="tl_gray">%s</span>', $parentData);
//                    }
            }
        }

        $event->setArgs($args);
    }
}
