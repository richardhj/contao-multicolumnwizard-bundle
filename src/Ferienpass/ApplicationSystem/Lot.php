<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\ApplicationSystem;

use Contao\Model\Event\PreSaveModelEvent;
use ContaoCommunityAlliance\DcGeneral\Contao\DataDefinition\Definition\Contao2BackendViewDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\Contao\Dca\Populator\DataProviderPopulator;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\ModelToLabelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PrePersistModelEvent;
use ContaoCommunityAlliance\DcGeneral\Factory\Event\PopulateEnvironmentEvent;
use Ferienpass\DcGeneral\View\OfferAttendancesView;
use Ferienpass\Event\UserSetApplicationEvent;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\AttendanceStatus;


/**
 * Class Lot
 *
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
            UserSetApplicationEvent::NAME  => [
                'setNewAttendance',
            ],
            PreSaveModelEvent::NAME        => [
                ['setAttendanceStatus'],
            ],
            PrePersistModelEvent::NAME     => [
                ['setAttendanceStatusDcGeneral']
            ],
            PopulateEnvironmentEvent::NAME => [
                ['populateEnvironmentForAttendancesChildTable', DataProviderPopulator::PRIORITY + 50],
            ],
            ModelToLabelEvent::NAME        => [
                ['addAttendancesEditLinkInOfferListView', -10],
            ],
        ];
    }


    public function setNewAttendance(UserSetApplicationEvent $event)
    {
        $this->setNewAttendanceInDatabase($event->getOffer(), $event->getParticipant());
    }


    /**
     * Save the "waiting" status for one attendance per default
     *
     * @param PreSaveModelEvent $event
     */
    public function setAttendanceStatus(PreSaveModelEvent $event)
    {
        /** @var Attendance $attendance */
        $attendance = $event->getModel();

        if (!$attendance instanceof Attendance || null !== $attendance->getStatus()) {
            return;
        }

        $data = $event->getData();

        // Set status
        $newStatus      = AttendanceStatus::findWaiting();
        $data['status'] = $newStatus->id;

        // Update sorting afterwards
        $lastAttendance = Attendance::findLastByOfferAndStatus($attendance->offer, $data['status']);
        $sorting        = (null !== $lastAttendance) ? $lastAttendance->sorting : 0;
        $sorting += 128;
        $data['sorting'] = $sorting;

        $event->setData($data);
    }


    /**
     * Save the "waiting" status for one attendance per default
     *
     * @param PrePersistModelEvent $event
     */
    public function setAttendanceStatusDcGeneral(PrePersistModelEvent $event)
    {
        if (Attendance::getTable() !== $event->getEnvironment()->getDataDefinition()->getName()) {
            return;
        }

        $model = $event->getModel();

        if ($model->getProperty('status')) {
            return;
        }

        // Set status
        $newStatus = AttendanceStatus::findWaiting();
        $model->setProperty('status', $newStatus->id);

        // Update sorting afterwards
        $lastAttendance = Attendance::findLastByOfferAndStatus(
            $model->getProperty('offer'),
            $model->getProperty('status')
        );
        $sorting        = (null !== $lastAttendance) ? $lastAttendance->sorting : 0;
        $sorting += 128;
        $model->setProperty('sorting', $sorting);
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
              && 'mm_ferienpass' === $definition
                    ->getBasicDefinition()
                    ->getParentDataProvider())
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
        $listing     = $viewSection->getListingConfig();
        $formatter   = $listing->getLabelFormatter($definition->getName());

        $propertyNames   = $formatter->getPropertyNames();
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
        $model      = $event->getModel();
        $definition = $event->getEnvironment()->getDataDefinition();

        // Not attendances for offer MetaModel
        if (!($definition->getName() === Attendance::getTable()
              && 'mm_ferienpass' === $definition
                    ->getBasicDefinition()
                    ->getParentDataProvider())
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
                        '<a href="contao/main.php?do=metamodel_mm_participant&amp;table=tl_ferienpass_attendance&amp;pid=mm_participant::%1$u&amp;popup=1&amp;nb=1&amp;rt=%4$s" class="open_participant_attendances" title="%3$s" onclick="Backend.openModalIframe({\'width\':768,\'title\':\'%3$s\',\'url\':this.href});return false">%2$s</a>',
                        // Member ID
                        $model->getProperty('participant'),
                        // Link
                        '<i class="fa fa-external-link tl_gray"></i> ' . Attendance::countByParticipant(
                            $model->getProperty('participant')
                        ) . ' Anmeldungen gesamt',
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
