<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\Age;

use Doctrine\DBAL\Connection;
use MetaModels\Attribute\AbstractSimpleAttributeTypeFactory;
use MetaModels\Helper\TableManipulator;


/**
 * Class AttributeTypeFactory
 *
 * @package MetaModels\Attribute\Age
 */
class AttributeTypeFactory extends AbstractSimpleAttributeTypeFactory
{

    /**
     * {@inheritDoc}
     */
    public function __construct(Connection $connection, TableManipulator $tableManipulator)
    {
        parent::__construct($connection, $tableManipulator);

        $this->typeName  = 'age';
        $this->typeIcon  = 'assets/ferienpass/core/img/fp_age.png';
        $this->typeClass = Age::class;
    }
}
