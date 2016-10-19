<?php
use Ferienpass\Event\NotificationSubscriber;
use Ferienpass\Helper\Dca;


global $container;

return [
    new Dca(),
    new NotificationSubscriber(),
    $container['ferienpass.applicationsystem'],
];
