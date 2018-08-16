<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\Age;

use Doctrine\DBAL\Connection;
use MetaModels\Attribute\AbstractAttributeTypeFactory;

/**
 * Class AttributeTypeFactory
 *
 * @package MetaModels\Attribute\Age
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
     * {@inheritDoc}
     */
    public function __construct(Connection $connection)
    {
        parent::__construct();

        $this->connection = $connection;
        $this->typeName   = 'age';
        $this->typeIcon   = 'bundles/metamodelsattributetext/text.png';
        $this->typeClass  = Age::class;
    }

    /**
     * {@inheritDoc}
     */
    public function createInstance($information, $metaModel)
    {
        return new $this->typeClass($metaModel, $information, $this->connection);
    }
}
