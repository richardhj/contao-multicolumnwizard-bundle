<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Richardhj\ContaoFerienpassBundle\ApplicationSystem;

use Contao\Model\Event\PreSaveModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PrePersistModelEvent;
use Richardhj\ContaoFerienpassBundle\Event\UserSetApplicationEvent;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;


/**
 * Class Lot
 *
 * @package Richardhj\ContaoFerienpassBundle\ApplicationSystem
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
                ['setNewAttendance'],
            ],
            PreSaveModelEvent::NAME        => [
                ['setAttendanceStatus'],
            ],
            PrePersistModelEvent::NAME     => [
                ['setAttendanceStatusDcGeneral']
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
        /** @var Attendance $model */
        $model = $event->getModel();
        if (!$model instanceof Attendance || null !== $model->getStatus()) {
            return;
        }

        $data = $event->getData();

        // Set status
        $newStatus      = AttendanceStatus::findWaiting();
        $data['status'] = $newStatus->id;

        // Update sorting afterwards
        $lastAttendance = Attendance::findLastByOfferAndStatus($model->offer, $data['status']);
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

        $sorting = (null !== $lastAttendance) ? $lastAttendance->sorting : 0;
        $sorting += 128;
        $model->setProperty('sorting', $sorting);
    }
}
