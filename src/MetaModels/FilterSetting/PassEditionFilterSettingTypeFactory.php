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

namespace Richardhj\ContaoFerienpassBundle\MetaModels\FilterSetting;

use MetaModels\Filter\Setting\AbstractFilterSettingTypeFactory;
use MetaModels\Filter\Setting\ICollection;
use MetaModels\Filter\Setting\ISimple;
use Symfony\Bridge\Doctrine\ManagerRegistry;

/**
 * Class PassEditionFilterSettingTypeFactory
 *
 * @package Richardhj\ContaoFerienpassBundle\MetaModels\FilterSetting
 */
class PassEditionFilterSettingTypeFactory extends AbstractFilterSettingTypeFactory
{
    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * PassEditionFilterSettingTypeFactory constructor.
     *
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        parent::__construct();

        $this->doctrine = $doctrine;

        $this
            ->setTypeName('pass_edition')
            ->setTypeIcon('bundles/richardhjcontaoferienpass/img/filter_fp_age.png')
            ->setTypeClass(PassEdition::class)
            ->allowAttributeTypes('select');
    }

    /**
     * Create a new instance with the given information.
     *
     * @param array       $information    The filter setting information.
     *
     * @param ICollection $filterSettings The filter setting instance the filter setting shall be created for.
     *
     * @return ISimple
     */
    public function createInstance($information, $filterSettings): ISimple
    {
        return new PassEdition($filterSettings, $information, $this->doctrine);
    }
}
