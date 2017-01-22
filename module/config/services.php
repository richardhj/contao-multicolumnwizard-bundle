<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

use Ferienpass\ApplicationSystem\AbstractApplicationSystem;
use Ferienpass\Model\ApplicationSystem;


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

        try {
            $result = $database
                ->prepare(
                    "SELECT type "
                    ."FROM {$table} "
                    ."WHERE (start='' OR start<='$time') AND (stop='' OR stop>'".($time + 60)."') AND published='1'"
                )
                ->limit(1)
                ->execute();

            if (1 === $result->numRows) {
                /** @var AbstractApplicationSystem $applicationSystem */
                $applicationSystem = $container['ferienpass.applicationsystem.'.$result->type];
                $applicationSystem->setModel((new ApplicationSystem($result)));

                return $applicationSystem;
            }

        } finally {
            return new Ferienpass\ApplicationSystem\NoOp();
        }

    }
);

$container['ferienpass.pass-release.show-current'] = 2;
$container['ferienpass.pass-release.edit-current'] = 1;
$container['ferienpass.pass-release.edit-previous'] = 1;

$container['ferienpass.attendance-status'] = [
    'confirmed',
    'waitlisted',
    'waiting',
    'error',
];
