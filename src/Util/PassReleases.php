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

namespace Richardhj\ContaoFerienpassBundle\Util;


use Doctrine\DBAL\Connection;
use MetaModels\Filter\Rules\SimpleQuery;
use MetaModels\IFactory;
use MetaModels\IItem;

class PassReleases
{

    /**
     * @var Connection
     */
    private $connection;

    /**
     * PassReleases constructor.
     *
     * @param Connection $connection The database connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get the pass release that is the nearest one in the future and is still editable.
     *
     * @return IItem|null
     */
    public function getPassReleaseToEdit(): ?array
    {
        $qb = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('tl_ferienpass_edition')
            ->where('holiday_begin>:time')
            ->andWhere('host_edit_end>:time')
            ->orderBy('holiday_begin')
            ->setParameter('time', time())
        ->execute();

        return $qb->fetch(\PDO::FETCH_ASSOC);
    }
}
