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

namespace Richardhj\ContaoFerienpassBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * This is the Bundle extension.
 */
class RichardhjContaoFerienpassExtension extends Extension
{

    /**
     * The files to load.
     *
     * @var string[]
     */
    private static $files = [
        'config.yml',
        'listeners.yml',
        'modules.yml',
        'services.yml',
        'dc-general/table/mm_ferienpass.yml',
        'dc-general/table/mm_host.yml',
        'dc-general/table/mm_participant.yml',
        'dc-general/table/tl_ferienpass_dataprocessing.yml',
    ];

    /**
     * Loads a specific configuration.
     *
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        foreach (self::$files as $file) {
            $loader->load($file);
        }
    }
}
