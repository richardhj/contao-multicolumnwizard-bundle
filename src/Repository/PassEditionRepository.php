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
use Doctrine\ORM\Query\Expr;
use Richardhj\ContaoFerienpassBundle\Entity\PassEdition;

class PassEditionRepository extends EntityRepository
{

    /**
     * Find one pass edition that has an active host editing stage.
     *
     * @return PassEdition|null
     */
    public function findOneToEdit(): ?PassEdition
    {
        $time = time();
        $qb0  = $this->_em->createQueryBuilder();
        $qb   = $this->createQueryBuilder('pass_edition')
            ->innerJoin(
                'pass_edition.tasks',
                'host_editing_stage',
                Expr\Join::WITH,
                $qb0->expr()->andX(
                    $qb0->expr()->eq('host_editing_stage.type', ':host_editing_stage'),
                    $qb0->expr()->lte('host_editing_stage.periodStart', ':editing_start'),
                    $qb0->expr()->gte('host_editing_stage.periodStop', ':editing_stop')
                )
            )
            ->setParameter('host_editing_stage', 'host_editing_stage')
            ->setParameter('editing_start', $time)
            ->setParameter('editing_stop', $time)
            ->getQuery();

        try {
            $result = $qb->setMaxResults(1)->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            $result = null;
        }

        return $result;
    }

    /**
     * @return PassEdition|null
     */
    public function findDefaultPassEditionForHost(): ?PassEdition
    {
        return $this->findOneToEdit() ?? $this->findOneToShowInFrontend();
    }

    /**
     * Find one pass release that has holidays currently.
     *
     * @return PassEdition|null
     */
    public function findOneToShowInFrontend(): ?PassEdition
    {
        $time = time();
        $qb0  = $this->_em->createQueryBuilder();
        $qb   = $this->createQueryBuilder('pass_edition')
            ->innerJoin(
                'pass_edition.tasks',
                'period',
                Expr\Join::WITH,
                $qb0->expr()->andX(
                    $qb0->expr()->eq('period.type', ':period'),
                    $qb0->expr()->lte('period.periodStart', ':period_start'),
                    $qb0->expr()->gte('period.periodStop', ':period_stop')
                )
            )
            ->setParameter('period', 'show_offers')
            ->setParameter('period_start', $time)
            ->setParameter('period_stop', $time)
            ->getQuery();

        try {
            $result = $qb->setMaxResults(1)->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            $result = null;
        }

        return $result;
    }

    /**
     * Find one pass release that has holidays currently.
     *
     * @return PassEdition|null
     */
    public function findOneWithCurrentHoliday(): ?PassEdition
    {
        $time = time();
        $qb0  = $this->_em->createQueryBuilder();
        $qb   = $this->createQueryBuilder('pass_edition')
            ->innerJoin(
                'pass_edition.tasks',
                'holiday',
                Expr\Join::WITH,
                $qb0->expr()->andX(
                    $qb0->expr()->eq('holiday.type', ':holiday'),
                    $qb0->expr()->lte('holiday.periodStart', ':holiday_start'),
                    $qb0->expr()->gte('holiday.periodStop', ':holiday_stop')
                )
            )
            ->setParameter('holiday', 'holiday')
            ->setParameter('holiday_start', $time)
            ->setParameter('holiday_stop', $time)
            ->getQuery();

        try {
            $result = $qb->setMaxResults(1)->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            $result = null;
        }

        return $result;
    }
}
