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

namespace Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem;


use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\FilesystemInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;


/**
 * Class Local
 *
 * @package Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem
 */
class Local implements FilesystemInterface
{

    /**
     * The filesystem component.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * The kernel project directory.
     *
     * @var string
     */
    private $kernelProjectDir;

    /**
     * Local constructor.
     *
     * @param Filesystem $filesystem       The filesystem component.
     * @param string     $kernelProjectDir The kernel project directory.
     */
    public function __construct(Filesystem $filesystem, string $kernelProjectDir)
    {
        $this->filesystem       = $filesystem;
        $this->kernelProjectDir = $kernelProjectDir;
    }

    /**
     * @param array          $files The file paths to handle.
     *
     * @param DataProcessing $model The model.
     *
     * @return void
     *
     * @throws IOException
     * @throws FileNotFoundException
     */
    public function processFiles(array $files, DataProcessing $model): void
    {
        $pathPrefix = $model->path_prefix ? $model->path_prefix . '/' : '';

        foreach ($files as $path) {
            $normalizedPath = str_replace($model->getTmpPath() . '/', '', $path);
            $this->filesystem->copy(
                $this->kernelProjectDir . '/' . $path,
                $this->kernelProjectDir . '/web/share/' . $pathPrefix . $normalizedPath
            );
        }
    }
}
