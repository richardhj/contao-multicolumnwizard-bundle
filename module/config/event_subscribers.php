<?php
use Ferienpass\Event\NotificationSubscriber;


global $container;

return [
    new NotificationSubscriber(),
    $container['ferienpass.applicationsystem'],
];
