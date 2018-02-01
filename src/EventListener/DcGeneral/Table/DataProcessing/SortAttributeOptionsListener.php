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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\DataProcessing;


use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use Doctrine\DBAL\Connection;
use Richardhj\ContaoFerienpassBundle\Model\Offer;

class SortAttributeOptionsListener
{

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var Offer
     */
    private $offerModel;

    /**
     * SortAttributeOptionsListener constructor.
     *
     * @param Connection $connection
     * @param Offer      $offerModel
     */
    public function __construct(Connection $connection, Offer $offerModel)
    {
        $this->connection = $connection;
        $this->offerModel = $offerModel;
    }

    /**
     * @param GetPropertyOptionsEvent $event The event.
     */
    public function handle(GetPropertyOptionsEvent $event): void
    {
        if (('metamodel_sortby' !== $event->getPropertyName())
            || ('tl_ferienpass_dataprocessing' !== $event->getModel()->getProviderName())
        ) {
            return;
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('colName', 'name')
            ->from('tl_metamodel_attribute')
            ->where('pid=:metamodel')
            ->setParameter('metamodel', $this->offerModel->getMetaModel()->get('id'))
            ->execute();

        $options = [];
        while ($row = $statement->fetch(\PDO::FETCH_OBJ)) {
            $options[$row->colName] = $row->name;
        }

        $event->setOptions($options);
    }
}
