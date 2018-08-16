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
    private static function getNotificationTokens($participant, $offer): array
    {
        $tokens = [];

        // Add all offer fields
        foreach ($offer->getMetaModel()->getAttributes() as $name => $attribute) {
            $parsed = $offer->parseAttribute($name);

            $tokens['offer_' . $name] = $parsed['text'];
        }

        // Add all the participant fields
        foreach ($participant->getMetaModel()->getAttributes() as $name => $attribute) {
            $parsed = $participant->parseAttribute($name);

            $tokens['participant_' . $name] = $parsed['text'];
        }

        // Add all the parent's member fields
        foreach ((array) $participant->get('pmember') as $k => $v) {
            $tokens['member_' . $k] = $v;
        }

        // Add the participant's email
        $tokens['participant_email'] = $tokens['participant_email'] ?: $tokens['member_email'];

        // Add the host's email
        $host = $offer->get('host');

        $tokens['host_email'] = $host['email'];

        // Add the admin's email
        $tokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];

        return $tokens;
    }
}
