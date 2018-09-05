<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\DataProcessing;


use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\Event\GetPropertyOptionsEvent;
use Doctrine\DBAL\Connection;
use Richardhj\ContaoFerienpassBundle\Model\Offer;

/**
 * Class FilterOptionsListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\DataProcessing
 */
class FilterOptionsListener
{

    /**
     * The database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * The offer.
     *
     * @var Offer
     */
    private $offerModel;

    /**
     * FilterOptionsListener constructor.
     *
     * @param Connection $connection The database connection.
     * @param Offer      $offerModel The offer.
     */
    public function __construct(Connection $connection, Offer $offerModel)
    {
        $this->connection = $connection;
        $this->offerModel = $offerModel;
    }

    /**
     * Set the available filters as options.
     *
     * @param GetPropertyOptionsEvent $event The event.
     */
    public function handle(GetPropertyOptionsEvent $event): void
    {
        if (('metamodel_filtering' !== $event->getPropertyName())
            || ('tl_ferienpass_dataprocessing' !== $event->getModel()->getProviderName())
        ) {
            return;
        }

        $metaModel = $this->offerModel->getMetaModel();
        $statement = $this->connection->createQueryBuilder()
            ->select('id', 'name')
            ->from('tl_metamodel_filter')
            ->where('pid=:metamodel')
            ->setParameter('metamodel', $metaModel->get('id'))
            ->execute();

        $options = [];
        foreach ($statement->fetchAll(\PDO::FETCH_OBJ) as $option) {
            $options[$option->id] = $option->name;
        }

        $event->setOptions($options);
    }
}
