<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2019 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2019 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmFerienpass;

use Contao\MemberModel;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;

/**
 * Class ContactPersonOptionsListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmFerienpass
 */
class ContactPersonOptionsListener
{

    /**
     * Only show contact persons belonging to the host.
     *
     * @param GetPropertyOptionsEvent $event The event.
     */
    public function onGetPropertyOptions(GetPropertyOptionsEvent $event): void
    {
        $propertyName = $event->getPropertyName();
        $environment  = $event->getEnvironment();

        if ('contact_person' !== $propertyName || 'mm_ferienpass' !== $environment->getDataDefinition()->getName()) {
            return;
        }

        $options = [];
        $hostId  = $event->getModel()->getProperty('host');

        // Fetch all allowed members.
        $members = MemberModel::findBy('ferienpass_host', $hostId);

        // Rebuild options array.
        if (null !== $members) {
            while ($members->next()) {
                $options[$members->id] = sprintf(
                    '%s %s%s%s%s',
                    $members->firstname,
                    $members->lastname,
                    $members->phone ? ', ' . $members->phone : '',
                    $members->mobile ? ', ' . $members->mobile : '',
                    $members->email ? ', ' . $members->email : ''
                );
            }
        }

        $event->setOptions($options);
    }
}
