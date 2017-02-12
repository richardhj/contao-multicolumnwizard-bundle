<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\MetaModels\Attribute\Age;

use MetaModels\Attribute\AbstractAttributeTypeFactory;


/**
 * Class AttributeTypeFactory
 * @package MetaModels\Attribute\Age
 */
class AttributeTypeFactory extends AbstractAttributeTypeFactory
{

    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();

        $this->typeName = 'age';
        $this->typeIcon = 'assets/ferienpass/backend/img/fp_age.png';
        $this->typeClass = 'Ferienpass\MetaModels\Attribute\Age\Age';
    }
}
