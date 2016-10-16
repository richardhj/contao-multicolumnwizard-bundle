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


use Ferienpass\Model\Attendance;
use Ferienpass\Model\AttendanceStatus;
use Ferienpass\Model\Config as FerienpassConfig;
use MetaModels\IItem;


class FirstCome extends AbstractApplicationSystem
{

    /**
     * {@inheritdoc}
     */
    public function findAttendanceStatus(Attendance $attendance, IItem $offer)
    {
        // Is current status locked?
        /** @var AttendanceStatus $status */
        if ($attendance->getStatus()->locked) {
            return $attendance->getStatus();
        }

        // Offers without usage of application list or without limit
        if (!$offer->get(FerienpassConfig::getInstance()->offer_attribute_applicationlist_active)
            || !($max = $offer->get(FerienpassConfig::getInstance()->offer_attribute_applicationlist_max))
        ) {
            return AttendanceStatus::findConfirmed();
        }

        $position = $attendance->getPosition();

        if (null !== $position) {
            if ($position <= $max) {
                return AttendanceStatus::findConfirmed();
            } else {
                return AttendanceStatus::findWaitlisted();
            }
        } // Attendance not saved yet
        else {
            if (Attendance::countParticipants($offer->get('id')) < $max) {
                return AttendanceStatus::findConfirmed();
            } else {
                return AttendanceStatus::findWaitlisted();
            }
        }
    }
}
