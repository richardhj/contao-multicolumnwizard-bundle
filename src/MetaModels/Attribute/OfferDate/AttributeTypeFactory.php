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

namespace Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\OfferDate;

use Doctrine\DBAL\Connection;
use MetaModels\Attribute\AbstractAttributeTypeFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class AttributeTypeFactory
 *
 * @package MetaModels\Attribute\OfferDate
 */
class AttributeTypeFactory extends AbstractAttributeTypeFactory
{

    /**
     * Database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * {@inheritDoc}
     */
    public function __construct(Connection $connection, EventDispatcherInterface $dispatcher)
    {
        parent::__construct();

        $this->connection = $connection;
        $this->dispatcher = $dispatcher;
        $this->typeName   = 'offer_date';
        $this->typeIcon   = 'bundles/metamodelsattributetimestamp/timestamp.png';
        $this->typeClass  = OfferDate::class;
    }


    /**
     * {@inheritDoc}
     */
    public function createInstance($information, $metaModel)
    {
        return new $this->typeClass($metaModel, $information, $this->connection, $this->dispatcher);
    }
}
