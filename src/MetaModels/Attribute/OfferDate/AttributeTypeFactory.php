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

namespace Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\OfferDate;

use MetaModels\Attribute\AbstractAttributeTypeFactory;


/**
 * Class AttributeTypeFactory
 * @package MetaModels\Attribute\OfferDate
 */
class AttributeTypeFactory extends AbstractAttributeTypeFactory
{

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->typeName = 'offer_date';
        $this->typeIcon = 'assets/ferienpass/core/img/fp_age.png';
        $this->typeClass = 'Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\OfferDate\OfferDate';
    }
}
