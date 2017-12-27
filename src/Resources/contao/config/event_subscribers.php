<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
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
