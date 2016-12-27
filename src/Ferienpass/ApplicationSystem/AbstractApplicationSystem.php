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


use MetaModels\IItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


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
}
