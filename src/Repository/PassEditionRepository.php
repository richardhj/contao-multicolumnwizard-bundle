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


use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Richardhj\ContaoFerienpassBundle\Entity\PassEdition;

class PassEditionRepository extends EntityRepository
{
    /**
     * @return PassEdition|null
     */
    public function findOneToEdit(): ?PassEdition
    {
        $qb = $this->createQueryBuilder('e')
            ->andWhere('e.holiday_begin > :time')
            ->andWhere('host_edit_end > :time')
            ->orderBy('e.holiday_begin', 'ASC')
            ->setParameter('time', time())
            ->getQuery();

        try {
            $result = $qb->setMaxResults(1)->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            $result = null;
        }

        return $result;
    }
}
