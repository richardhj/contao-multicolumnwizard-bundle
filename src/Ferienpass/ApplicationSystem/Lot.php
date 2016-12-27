<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\ApplicationSystem;

use ContaoCommunityAlliance\DcGeneral\Contao\DataDefinition\Definition\Contao2BackendViewDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\Contao\Dca\Populator\DataProviderPopulator;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ModelToLabelEvent;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\PopulateEnvironmentEvent;
use Ferienpass\DcGeneral\View\OfferAttendancesView;
use Ferienpass\Event\ChangeAttendanceStatusEvent;
use Ferienpass\Event\SaveAttendanceEvent;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\AttendanceStatus;
use Ferienpass\Model\Config as FerienpassConfig;
use Symfony\Component\EventDispatcher\EventDispatcher;


/**
 * Class Lot
 * @package Ferienpass\ApplicationSystem
 */
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
                ['addAttendancesEditLinkInOfferListView', -10],
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
        $attendance = $event->getModel();

        if (null !== ($oldStatus = $attendance->getStatus())) {
            return;
        }

        $newStatus = AttendanceStatus::findWaiting();

        $attendance->status = $newStatus->id;
        $attendance->save();
    }


    /**
     * Use the OfferAttendancesView if applicable
     *
     * @param PopulateEnvironmentEvent $event
     */
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

        // Alter view
        $view = new OfferAttendancesView();
        $view->setEnvironment($environment);
        $environment->setView($view);


        // Add "attendances" property
        /** @var Contao2BackendViewDefinitionInterface $viewSection */
        $viewSection = $definition->getDefinition(Contao2BackendViewDefinitionInterface::NAME);
        $listing = $viewSection->getListingConfig();
        $formatter = $listing->getLabelFormatter($definition->getName());

        $propertyNames = $formatter->getPropertyNames();
        $propertyNames[] = 'attendances';
        $formatter->setPropertyNames($propertyNames);
    }


    /**
     * Show the participant's overall attendances and show an popup link
     *
     * @param ModelToLabelEvent $event
     */
    public function addAttendancesEditLinkInOfferListView(ModelToLabelEvent $event)
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

        // Adjust the label
        foreach ($args as $k => $v) {
            switch ($k) {
                case 'attendances':
                    $args[$k] = sprintf(
                        '<a href="contao/main.php?do=metamodel_mm_participants&amp;table=tl_ferienpass_attendance&amp;pid=mm_participants::%1$u&amp;popup=1&amp;nb=1&amp;rt=%4$s" class="open_participant_attendances" title="%3$s" onclick="Backend.openModalIframe({\'width\':768,\'title\':\'%3$s\',\'url\':this.href});return false">%2$s</a>',
                        // Member ID
                        $model->getProperty('participant'),
                        // Link
                        '<i class="fa fa-external-link tl_gray"></i> '.Attendance::countByParticipant(
                            $model->getProperty('participant')
                        ).' Anmeldungen gesamt',
                        // Member edit description
                        sprintf(
                            $GLOBALS['TL_LANG']['tl_member']['edit'][1],
                            ''
                        ),
                        REQUEST_TOKEN
                    );
                    break;
            }
        }

        $event->setArgs($args);
    }
}
