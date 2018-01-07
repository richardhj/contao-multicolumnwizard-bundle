<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE
 */

use Richardhj\ContaoFerienpassBundle\ApplicationSystem\AbstractApplicationSystem;
use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem as ApplicationSystemModel;
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\IMetaModelsServiceContainer;

/** @var Pimple $container */

$container['ferienpass.applicationsystem.firstcome'] = $container->share(
    function () {
        return new Richardhj\ContaoFerienpassBundle\ApplicationSystem\FirstCome();
    }
);

$container['ferienpass.applicationsystem.lot'] = $container->share(
    function () {
        return new Richardhj\ContaoFerienpassBundle\ApplicationSystem\Lot();
    }
);

$container['ferienpass.applicationsystem'] = $container->share(
    function ($container) {
        /** @var \Database $database */
        $database = $container['database.connection'];

        $time  = time();
        $table = Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem::getTable();

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
            return new Richardhj\ContaoFerienpassBundle\ApplicationSystem\NoOp();
        }

        return new Richardhj\ContaoFerienpassBundle\ApplicationSystem\NoOp();
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

