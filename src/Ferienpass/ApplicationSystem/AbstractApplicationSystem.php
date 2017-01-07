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

use Ferienpass\Helper\Message;
use Ferienpass\Model\Attendance;
use MetaModels\IItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


/**
 * Class AbstractApplicationSystem
 * @package Ferienpass\ApplicationSystem
 */
abstract class AbstractApplicationSystem implements EventSubscriberInterface
{

    /**
     * Get notification tokens
     *
     * @param IItem $participant
     * @param IItem $offer
     *
     * @return array
     */
    public static function getNotificationTokens($participant, $offer)
    {
        $tokens = [];

        // Add all offer fields
        foreach ($offer->getMetaModel()->getAttributes() as $name => $attribute) {
            $tokens['offer_'.$name] = $offer->get($name);
        }

        // Add all the participant fields
        foreach ($participant->getMetaModel()->getAttributes() as $name => $attribute) {
            $tokens['participant_'.$name] = $participant->get($name);
        }

        // Add all the parent's member fields
        $ownerAttribute = $participant->getMetaModel()->getAttributeById(
            $participant->getMetaModel()->get('owner_attribute')
        );
        foreach ($participant->get($ownerAttribute->getColName()) as $k => $v) {
            $tokens['member_'.$k] = $v;
        }

        // Add the participant's email
        $tokens['participant_email'] = $tokens['participant_email'] ?: $tokens['member_email'];

        // Add the host's email
        $tokens['host_email'] = $offer->get($offer->getMetaModel()->get('owner_attribute'))['email'];

        // Add the admin's email
        $tokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];

        return $tokens;
    }


    /**
     * @param IItem $offer
     * @param IItem $participant
     */
    protected function setNewAttendanceInDatabase(IItem $offer, IItem $participant)
    {
        // Check if participant id allowed here and attendance not existent yet
        if (Attendance::isNotExistent($participant->get('id'), $offer->get('id'))) {
            $attendance = new Attendance();
            $attendance->tstamp = time();
            $attendance->created = time();
            $attendance->offer = $offer->get('id');
            $attendance->participant = $participant->get('id');
            $attendance->save();

        } // Attendance already exists
        else {
            Message::addError($GLOBALS['TL_LANG']['MSC']['applicationList']['error']);
        }
    }
}
