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

namespace Richardhj\ContaoFerienpassBundle\Repository;


use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Richardhj\ContaoFerienpassBundle\Entity\PassEdition;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PassEditionRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PassEdition::class);
    }

    /**
     * @return PassEdition[]
     */
    public function findOneToEdit(): array
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.holiday_begin > :time')
            ->andWhere('host_edit_end > :time')
            ->orderBy('e.holiday_begin', 'ASC')
            ->setParameter('time', time())
            ->getQuery();

        return $qb->execute();

        // to get just one result:
        // $product = $qb->setMaxResults(1)->getOneOrNullResult();
    }
}
