<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

$container['ferienpass.applicationsystem.firstcome'] = function () {
    return new Ferienpass\ApplicationSystem\FirstCome();
};

$container['ferienpass.applicationsystem.lot'] = function () {
    return new Ferienpass\ApplicationSystem\Lot();
};

$container['ferienpass.applicationsystem'] = $container->share(
    function ($container) {
        /** @var \Database $database */
        $database = $container['database.connection'];

        $time = time();
        $table = Ferienpass\Model\ApplicationSystem::getTable();

        $result = $database
            ->query(
                "SELECT type "
                ."FROM {$table} "
                ."WHERE (start='' OR start<='$time') AND (stop='' OR stop>'".($time + 60)."') AND published='1'"
            );

        if (1 === $result->numRows) {
            return $container['ferienpass.applicationsystem.'.$result->type];
        }

        return new Ferienpass\ApplicationSystem\NoOp();
    }
);

$container['ferienpass.attendance-status'] = [
    'confirmed',
    'waitlisted',
    'waiting',
    'error',
];
