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


use League\Flysystem\Filesystem as Flysystem;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing as DataProcessingModel;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\FilesystemInterface;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;

/**
 * Class Dropbox
 *
 * @package Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem
 */
class Dropbox implements FilesystemInterface, Filesystem\TwoWaySyncInterface
{

    /**
     * @var string
     */
    private $kernelProjectDir;

    /**
     * Dropbox constructor.
     *
     * @param string $kernelProjectDir
     */
    public function __construct(string $kernelProjectDir)
    {
        $this->kernelProjectDir = $kernelProjectDir;
    }

    /**
     * {@inheritdoc}
     */
    public function processFiles(array $files, DataProcessingModel $model): void
    {
        $dropboxClient = new DropboxClient($model->dropbox_access_token);
        $adapter       = new DropboxAdapter($dropboxClient, 'ferienpass.online/'.$model->path_prefix);
        $filesystem    = new Flysystem($adapter);

        foreach ($files as $path) {
//            try {
            // Trim tmp path from path
            $normalizedPath = str_replace($model->getTmpPath().'/', '', $path);
            $filesystem->put($normalizedPath, file_get_contents($this->kernelProjectDir.'/'.$path));

//            } catch (Exception_BadRequest $e) {
//                // File was not uploaded
//                // often because it is on the ignored file list
//                $this->dispatcher->dispatch(
//                    ContaoEvents::SYSTEM_LOG,
//                    new LogEvent(
//                        sprintf('%s. Data processing ID %u', $e->getMessage(), $this->getModel()->id),
//                        __METHOD__,
//                        TL_GENERAL
//                    )
//                );
//            } catch (Exception_NetworkIO $e) {
//                // File was not uploaded
//                // Connection refused
//                $this->dispatcher->dispatch(
//                    ContaoEvents::SYSTEM_LOG,
//                    new LogEvent(
//                        sprintf('%s. Data processing ID %u', $e->getMessage(), $this->getModel()->id),
//                        __METHOD__,
//                        TL_ERROR
//                    )
//                );
//            }
        }
    }

    /**
     * {@inheritdoc}
     * @throws \League\Flysystem\FilesystemNotFoundException
     */
    public function triggerBackSync(): void
    {
        $this->syncFromRemoteDropbox();
    }

    /**
     * Sync from remote dropbox by fetching the delta (last edited files in dropbox)
     *
     * @throws \League\Flysystem\FilesystemNotFoundException
     */
    protected function syncFromRemoteDropbox()
    {
//        if (!$this->getModel()->sync) {
//            return;
//        }
//
//        /** @var MountManager $mountManager */
//        $mountManager = System::getContainer()->get('oneup_flysystem.mount_manager');
//
//        $dbx = $mountManager->getFilesystem('dropbox');
//        $dbx->addPlugin(new DropboxDelta());
//
//        $delta = $dbx->getDelta($this->getModel()->dropbox_cursor ?: null);
//
//        // Save current cursor if "reset" is not set in response
//        $this->getModel()->dropbox_cursor =
//            !($this->getModel()->dropbox_cursor && $delta['reset'])
//                ? $delta['cursor']
//                : '';
//        $this->getModel()->save();
//
//        $files = $delta['entries'];
//
//        // Process static dirs
//        foreach (deserialize($this->getModel()->static_dirs, true) as $dirBin) {
//            $dir = (\FilesModel::findByPk($dirBin))->path;
//
//            foreach ($files as $entry) {
//                if ($dir !== $files['directory']) {
//                    continue;
//                }
//
//                // Remote file was deleted
//                if (null === $entry[1]) {
//                    if ($mountManager->delete('dbafs://'.$dir.'/'.basename($entry[0]))) {
//                        $this->dispatcher->dispatch(
//                            ContaoEvents::SYSTEM_LOG,
//                            new LogEvent(
//                                sprintf(
//                                    'File "%s" was deleted by dropbox synchronisation. Data processing ID %u',
//                                    $dir.'/'.basename($entry[0]),
//                                    $this->getModel()->id
//                                ),
//                                __METHOD__,
//                                TL_GENERAL
//                            )
//                        );
//
//                        continue;
//                    }
//
//                    $entry = $entry[1];
//
//                    if (!$mountManager->has('dbafs://'.$entry['path'])
//                        || $mountManager->getTimestamp('dropbox://'.$entry['path'])
//                           > $mountManager->getTimestamp('dbafs://'.$entry['path'])
//                    ) {
//                        $mountManager->put(
//                            'dbafs://'.$entry['path'],
//                            $mountManager->read('dropbox://'.$entry['path'])
//                        );
//                        $this->dispatcher->dispatch(
//                            ContaoEvents::SYSTEM_LOG,
//                            new LogEvent(
//                                sprintf(
//                                    'File "%s" was updated by dropbox synchronisation. Data processing ID %u',
//                                    $entry['path'],
//                                    $this->getModel()->id
//                                ),
//                                __METHOD__,
//                                TL_GENERAL
//                            )
//                        );
//                    }
//                }
//            }
//        }
//
//        // Trigger format handler sync
//        $formatHandler = $this->getModel()->getFormatHandler();
//        if ($formatHandler instanceof Format\TwoWaySyncInterface) {
//            $formatHandler->syncFilesFromRemoteSystem(
//                array_map(
//                    function ($value) {
//                        return $value[1];
//                    },
//                    $files
//                ),
//                'dropbox'
//            );
//        }
    }
}
