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

namespace Richardhj\ContaoFerienpassBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use MetaModels\CoreBundle\MetaModelsCoreBundle;
use MetaModels\FilterCheckboxBundle\MetaModelsFilterCheckboxBundle;
use MetaModels\FilterFromToBundle\MetaModelsFilterFromToBundle;
use Richardhj\ContaoFerienpassBundle\RichardhjContaoFerienpassBundle;

/**
 * Contao Manager plugin.
 */
class Plugin implements BundlePluginInterface
{

    /**
     * {@inheritdoc}
     */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(RichardhjContaoFerienpassBundle::class)
                ->setLoadAfter(
                    [
                        ContaoCoreBundle::class,
                        MetaModelsCoreBundle::class,
                        MetaModelsFilterCheckboxBundle::class,
                        MetaModelsFilterFromToBundle::class
                    ]
                ),
        ];
    }
}
