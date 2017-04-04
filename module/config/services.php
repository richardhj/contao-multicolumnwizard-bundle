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
use Ferienpass\Model\ApplicationSystem as ApplicationSystemModel;
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\IMetaModelsServiceContainer;


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

        $time  = time();
        $table = Ferienpass\Model\ApplicationSystem::getTable();

        try {
            $result = $database
                ->prepare(
                    "SELECT * "
                    . "FROM {$table} "
                    . "WHERE (start='' OR start<='$time') AND (stop='' OR stop>'" . ($time + 60)
                    . "') AND published='1'"
                )
                ->limit(1)
                ->execute();

            if (1 === $result->numRows) {
                /** @var AbstractApplicationSystem $applicationSystem */
                $applicationSystem = $container['ferienpass.applicationsystem.' . $result->type];
                $model             = new ApplicationSystemModel($result);
                $applicationSystem->setModel($model);

                return $applicationSystem;
            }

        } catch (\Exception $e) {
            return new Ferienpass\ApplicationSystem\NoOp();
        }

        return new Ferienpass\ApplicationSystem\NoOp();
    }
);

$container['ferienpass.pass-release.show-current']  = function () {
    global $container;
    /** @var IMetaModelsServiceContainer $serviceContainer */
    $serviceContainer = $container['metamodels-service-container'];

    $filterRule = new SimpleQuery('SELECT id FROM mm_ferienpass_release WHERE show_current=1');
    $metaModel  = $serviceContainer->getFactory()->getMetaModel('mm_ferienpass_release');
    $filter     = $metaModel->getEmptyFilter()->addFilterRule($filterRule);
    $release    = $metaModel->findByFilter($filter);

    return $release->getItem();
};
$container['ferienpass.pass-release.edit-current']  = function () {
    global $container;
    /** @var IMetaModelsServiceContainer $serviceContainer */
    $serviceContainer = $container['metamodels-service-container'];

    $filterRule = new SimpleQuery('SELECT id FROM mm_ferienpass_release WHERE edit_current=1');
    $metaModel  = $serviceContainer->getFactory()->getMetaModel('mm_ferienpass_release');
    $filter     = $metaModel->getEmptyFilter()->addFilterRule($filterRule);
    $release    = $metaModel->findByFilter($filter);

    return $release->getItem();
};
$container['ferienpass.pass-release.edit-previous'] = function () {
    global $container;
    /** @var IMetaModelsServiceContainer $serviceContainer */
    $serviceContainer = $container['metamodels-service-container'];

    $filterRule = new SimpleQuery('SELECT id FROM mm_ferienpass_release WHERE edit_previous=1');
    $metaModel  = $serviceContainer->getFactory()->getMetaModel('mm_ferienpass_release');
    $filter     = $metaModel->getEmptyFilter()->addFilterRule($filterRule);
    $release    = $metaModel->findByFilter($filter);

    return $release->getItem();
};

$container['ferienpass.attendance-status'] = [
    'confirmed',
    'waitlisted',
    'waiting',
    'error',
];

$container['ferienpass.dropbox.appId']     = 'qgbgzoptvlpyofc';
$container['ferienpass.dropbox.appSecret'] = '9fktiifz7cxhar6';
