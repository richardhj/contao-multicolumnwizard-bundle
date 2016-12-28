<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

$container['ferienpass.applicationsystem.firstcome'] = function ($container) {
    return new Ferienpass\ApplicationSystem\FirstCome();
};

$container['ferienpass.applicationsystem.lot'] = function ($container) {
    return new Ferienpass\ApplicationSystem\Lot();
};

$container['ferienpass.applicationsystem'] = $container->share(
    function ($container) {
        $current = Ferienpass\Model\ApplicationSystem::findCurrent();

        if (null === $current) {
            return null;
        }

        return $container['ferienpass.applicationsystem.'.$current->type];
    }
);

$container['ferienpass.attendance-status'] = [
    'confirmed',
    'waitlisted',
    'waiting',
    'error',
];
