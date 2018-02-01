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

class FilterOptionsListener
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
     * FilterOptionsListener constructor.
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
     *
     * @throws \RuntimeException
     */
    public function handle(GetPropertyOptionsEvent $event): void
    {
        if (('metamodel_filtering' !== $event->getPropertyName())
            || ('tl_ferienpass_dataprocessing' !== $event->getModel()->getProviderName())
        ) {
            return;
        }

        $metaModel = $this->offerModel->getMetaModel();
        if (null === $metaModel) {
            throw new \RuntimeException('MetaModel not found.');
        }

        $statement = $this->connection->createQueryBuilder()
            ->select('id', 'name')
            ->from('tl_metamodel_filter')
            ->where('pid=:metamodel')
            ->setParameter('metamodel', $metaModel->get('id'))
            ->execute();

        $options = $statement->fetchAll(\PDO::FETCH_COLUMN, 'name');

        $event->setOptions($options);
    }
}

