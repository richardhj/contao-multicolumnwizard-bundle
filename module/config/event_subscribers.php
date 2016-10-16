<?php
use Ferienpass\Event\NotificationCenterSubscriber;


global $container;

return [
    new NotificationCenterSubscriber(),
    $container['ferienpass.applicationsystem'],
];
