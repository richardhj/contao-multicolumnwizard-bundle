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

namespace Richardhj\ContaoFerienpassBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use ContaoCommunityAlliance\MetaPalettes\CcaMetaPalettesBundle;
use MetaModels\CoreBundle\MetaModelsCoreBundle;
use MetaModels\FilterCheckboxBundle\MetaModelsFilterCheckboxBundle;
use MetaModels\FilterFromToBundle\MetaModelsFilterFromToBundle;
use Richardhj\ContaoFerienpassBundle\RichardhjContaoFerienpassBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Contao Manager plugin.
 */
class Plugin implements BundlePluginInterface, RoutingPluginInterface
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
                        MetaModelsFilterFromToBundle::class,
                        CcaMetaPalettesBundle::class,
                    ]
                ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        return $resolver
            ->resolve(__DIR__.'/../Resources/config/routing.yml')
            ->load(__DIR__.'/../Resources/config/routing.yml');
    }
}
