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
use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem;

class ApplicationSystemFactory
{

    /**
     * @return ApplicationSystemInterface
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public static function create(): ApplicationSystemInterface
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
            /** @var ApplicationSystemInterface $applicationSystem */
            $applicationSystem = System::getContainer()->get('richardhj.ferienpass.application_system.'.$model->type);

            return $applicationSystem;
        }

        return new NoOp();
    }

    /**
     * @return FirstCome
     */
    public static function createFirstCome(): FirstCome
    {
        return new FirstCome(ApplicationSystemModel::findFirstCome());
    }

    /**
     * @return Lot
     */
    public static function createLot(): Lot
    {
        return new Lot(ApplicationSystemModel::findLot());
    }
}
