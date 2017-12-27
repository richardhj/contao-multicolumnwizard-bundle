<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

use Richardhj\ContaoFerienpassBundle\Helper\Dca;
use Richardhj\ContaoFerienpassBundle\Module\Subscriber as ModuleSubscriber;
use Richardhj\ContaoFerienpassBundle\Subscriber\Dca\Offer as DcaOffer;
use Richardhj\ContaoFerienpassBundle\Subscriber\NotificationSubscriber;


global $container;

return [
    new Dca(),
    new DcaOffer(),
    new NotificationSubscriber(),
    new ModuleSubscriber(),
    $container['ferienpass.applicationsystem'],
];
