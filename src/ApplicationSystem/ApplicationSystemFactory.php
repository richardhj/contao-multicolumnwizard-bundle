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
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

class ApplicationSystemFactory
{

    /**
     * @return ApplicationSystemInterface
     *
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    public static function create(): ApplicationSystemInterface
    {
        $model = ApplicationSystemModel::findCurrent();

        if (null !== $model) {
            /** @var ApplicationSystemInterface $applicationSystem */
            $applicationSystem = System::getContainer()->get('richardhj.ferienpass.application_system.' . $model->type);

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

    /**
     * @param mixed $id The id or the pass_edition row with id value.
     *
     * @return ApplicationSystemInterface
     */
    public static function findForPassEdition($id): ApplicationSystemInterface
    {
        $id = $id['id'] ?? $id;
        $connection = System::getContainer()->get('database_connection');
//        $statement = $connection->createQueryBuilder()
//            ->select('*')
//            ->from('tl_ferienpass_edition')
    }
}
