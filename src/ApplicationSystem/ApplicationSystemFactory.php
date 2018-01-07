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

namespace Richardhj\ContaoFerienpassBundle\ApplicationSystem;


use Contao\System;
use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem as ApplicationSystemModel;

class ApplicationSystemFactory
{

    public static function create()
    {
//        $time = time();
//        $expr = $this->connection->getExpressionBuilder();

        $model = ApplicationSystemModel::findCurrent();

//        $statement = $this->connection->createQueryBuilder()
//            ->select('*')
//            ->from(ApplicationSystem::getTable())
//            ->where($expr->orX()->add("start=''")->add('start<=:time'))
//            ->andWhere($expr->orX()->add("stop=''")->add('stop>:time2'))
//            ->setParameter('time', $time)
//            ->setParameter($time + 60)
//            ->setMaxResults(1)
//            ->execute();
//
//        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        if (null !== $model) {
            $applicationSystem = System::getContainer()->get('richardhj.ferienpass.application_system.'.$model->type);
            $applicationSystem->setModel($model);

            return $applicationSystem;
        }

        return new NoOp();
    }
}
