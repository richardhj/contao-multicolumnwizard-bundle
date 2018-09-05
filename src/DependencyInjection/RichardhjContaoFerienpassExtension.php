<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * This is the Bundle extension.
 */
class RichardhjContaoFerienpassExtension extends Extension implements PrependExtensionInterface
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
        'dc-general/table/tl_ferienpass_edition_task.yml',
    ];

    /**
     * Loads a specific configuration.
     *
     * @param array            $configs   The configurations.
     * @param ContainerBuilder $container The container builder.
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

    /**
     * Allow an extension to prepend the extension configurations.
     *
     * @param ContainerBuilder $container The container builder.
     */
    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig(
            'doctrine',
            [
                'orm' => [
                    'auto_mapping' => true,
                    'mappings'     => [
                        'RichardhjContaoFerienpassBundle' => []
                    ]
                ]
            ]
        );
    }
}
