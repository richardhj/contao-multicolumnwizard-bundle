<?php
/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\EventListener\Notification;


use MetaModels\IItem;

trait GetNotificationTokensTrait
{

    /**
     * Get notification tokens for notifications with type:
     * * application_list_status_change
     * * application_list_reminder
     *
     * @param IItem $participant
     * @param IItem $offer
     *
     * @return array
     */
    private static function getNotificationTokens($participant, $offer)
    {
        $tokens = [];

        // Add all offer fields
        foreach ($offer->getMetaModel()->getAttributes() as $name => $attribute) {
            $tokens['offer_' . $name] = $offer->parseAttribute($name)['text'];
        }

        // Add all the participant fields
        foreach ($participant->getMetaModel()->getAttributes() as $name => $attribute) {
            $tokens['participant_' . $name] = $participant->parseAttribute($name)['text'];
        }

        // Add all the parent's member fields
        $ownerAttribute = $participant->getMetaModel()->getAttributeById(
            $participant->getMetaModel()->get('owner_attribute')
        );
        foreach ($participant->get($ownerAttribute->getColName()) as $k => $v) {
            $tokens['member_' . $k] = $v;
        }

        // Add the participant's email
        $tokens['participant_email'] = $tokens['participant_email'] ?: $tokens['member_email'];

        // Add the host's email
        $tokens['host_email'] = $offer->get($offer->getMetaModel()->get('owner_attribute'))['email'];

        // Add the admin's email
        $tokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];

        return $tokens;
    }
}
