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


use Ferienpass\Event\SaveAttendanceEvent;
use Ferienpass\Model\AttendanceStatus;


class Lot extends AbstractApplicationSystem
{

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            SaveAttendanceEvent::NAME => [
                'updateAttendanceStatus',
            ],
        ];
    }


    public function updateAttendanceStatus(SaveAttendanceEvent $event)
    {
        $attendance = $event->getAttendance();

        if (null !== $attendance->getStatus()) {
            return;
        }

        $attendance->status = AttendanceStatus::findWaiting()->id;
        $attendance->save();
    }
}
