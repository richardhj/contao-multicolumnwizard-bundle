<?php

$container['ferienpass.applicationsystem.firstcome'] = function ($container) {
    return new Ferienpass\ApplicationSystem\FirstCome();
};

$container['ferienpass.applicationsystem.lot'] = function ($container) {
    return new Ferienpass\ApplicationSystem\Lot();
};

$container['ferienpass.applicationsystem'] = $container->share(
    function ($container) {
        return $container['ferienpass.applicationsystem.firstcome'];
    }
);
