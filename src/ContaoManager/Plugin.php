<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Richardhj\ContaoFerienpassBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use MetaModels\CoreBundle\MetaModelsCoreBundle;
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
                    ]
                ),
        ];
    }
}
