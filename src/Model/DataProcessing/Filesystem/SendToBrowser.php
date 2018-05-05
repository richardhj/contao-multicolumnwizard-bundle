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

namespace Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem;


use Contao\ZipWriter;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\FilesystemInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class SendToBrowser
 *
 * @package Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem
 */
class SendToBrowser implements FilesystemInterface
{

    /**
     * @var string
     */
    private $kernelProjectDir;

    /**
     * SendToBrowser constructor.
     *
     * @param string $kernelProjectDir The kernel project directory.
     */
    public function __construct(string $kernelProjectDir)
    {
        $this->kernelProjectDir = $kernelProjectDir;
    }

    /**
     * @param array          $files The file paths to handle.
     *
     * @param DataProcessing $model The model.
     *
     * @return void
     * @throws \Exception
     */
    public function processFiles(array $files, DataProcessing $model): void
    {
        $response = null;

        if (\count($files) > 1) {
            // Generate a zip file
            $zipWriter = new ZipWriter($model->getTmpPath().'/export.zip');

            foreach ($files as $path) {
                $normalizedPath = str_replace($model->getTmpPath().'/', '', $path);
                $zipWriter->addFile($normalizedPath);
            }

            $zipWriter->close();

            $response = new BinaryFileResponse(
                file_get_contents($this->kernelProjectDir.'/'.$model->getTmpPath().'/export.zip')
            );

            $response->headers->set(
                'Content-Disposition',
                $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    $model->export_file_name.'.zip'
                )
            );
        } elseif ($path = array_shift($files)) {
            $response = new BinaryFileResponse($this->kernelProjectDir.'/'.$path);
        }

        //clearstatcache(false, $file) see http://symfony.com/doc/2.3/components/http_foundation/introduction.html#serving-files

        if (null !== $response) {
            $response->send();
            exit;
        }
    }
}
