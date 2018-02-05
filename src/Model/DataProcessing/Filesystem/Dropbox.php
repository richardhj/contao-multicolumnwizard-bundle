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


use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\LogEvent;
use Dropbox\Exception_BadRequest;
use Dropbox\Exception_NetworkIO;
use Richardhj\ContaoFerienpassBundle\Flysystem\Plugin\DropboxDelta;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\FilesystemInterface;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Format;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem;
use MetaModels\IItems;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Class Dropbox
 *
 * @package Richardhj\ContaoFerienpassBundle\Model\DataProcessing\Filesystem
 */
class Dropbox implements FilesystemInterface, Filesystem\TwoWaySyncInterface
{
    /**
     * @var DataProcessing|\Model $model
     */
    private $model;

    /**
     * @var IItems $items
     */
    private $items;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * {@inheritdoc}
     */
    public function __construct(DataProcessing $model)
    {
        $this->model = $model;
    }

    /**
     * @return DataProcessing|\Model
     */
    public function getModel(): DataProcessing
    {
        return $this->model;
    }

    /**
     * @return IItems
     */
    public function getItems(): IItems
    {
        return $this->items;
    }

    /**
     * @param IItems $items
     *
     * @return FilesystemInterface
     */
    public function setItems(IItems $items): FilesystemInterface
    {
        $this->items = $items;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function processFiles(array $files)
    {
        // Make sure to mount dropbox
        $mountManager = $this->getModel()->getMountManager('dropbox');

        foreach ($files as $file) {
            try {
                // Trim tmp path from path
                $path = str_replace($this->getModel()->getTmpPath() . '/', '', $file['path']);

                $mountManager->put(
                    'dropbox://' . $path,
                    $mountManager->read('local://' . $file['path'])
                );

            } catch (Exception_BadRequest $e) {
                // File was not uploaded
                // often because it is on the ignored file list
                $this->dispatcher->dispatch(
                    ContaoEvents::SYSTEM_LOG,
                    new LogEvent(
                        sprintf('%s. Data processing ID %u', $e->getMessage(), $this->getModel()->id),
                        __METHOD__,
                        TL_GENERAL
                    )
                );
            } catch (Exception_NetworkIO $e) {
                // File was not uploaded
                // Connection refused
                $this->dispatcher->dispatch(
                    ContaoEvents::SYSTEM_LOG,
                    new LogEvent(
                        sprintf('%s. Data processing ID %u', $e->getMessage(), $this->getModel()->id),
                        __METHOD__,
                        TL_ERROR
                    )
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     * @throws \League\Flysystem\FilesystemNotFoundException
     */
    public function triggerBackSync()
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
        if (!$this->getModel()->sync) {
            return;
        }

        $mountManager = $this
            ->getModel()
            ->getMountManager('dropbox');

        $dbx = $mountManager->getFilesystem('dropbox');
        $dbx->addPlugin(new DropboxDelta());

        /** @noinspection PhpUndefinedMethodInspection */
        $delta = $dbx->getDelta($this->getModel()->dropbox_cursor ?: null);

        // Save current cursor if "reset" is not set in response
        $this->getModel()->dropbox_cursor =
            !($this->getModel()->dropbox_cursor && $delta['reset'])
                ? $delta['cursor']
                : '';
        $this->getModel()->save();

        $files = $delta['entries'];

        // Process static dirs
        foreach (deserialize($this->getModel()->static_dirs, true) as $dirBin) {
            $dir = (\FilesModel::findByPk($dirBin))->path;

            foreach ($files as $entry) {
                if ($dir !== $files['directory']) {
                    continue;
                }

                // Remote file was deleted
                if (null === $entry[1]) {
                    if ($mountManager->delete('dbafs://' . $dir . '/' . basename($entry[0]))) {
                        $this->dispatcher->dispatch(
                            ContaoEvents::SYSTEM_LOG,
                            new LogEvent(
                                sprintf(
                                    'File "%s" was deleted by dropbox synchronisation. Data processing ID %u',
                                    $dir . '/' . basename($entry[0]),
                                    $this->getModel()->id
                                ),
                                __METHOD__,
                                TL_GENERAL
                            )
                        );

                        continue;
                    }

                    $entry = $entry[1];

                    if (!$mountManager->has('dbafs://' . $entry['path'])
                        || $mountManager->getTimestamp('dropbox://' . $entry['path'])
                           > $mountManager->getTimestamp('dbafs://' . $entry['path'])
                    ) {
                        $mountManager->put(
                            'dbafs://' . $entry['path'],
                            $mountManager->read('dropbox://' . $entry['path'])
                        );
                        $this->dispatcher->dispatch(
                            ContaoEvents::SYSTEM_LOG,
                            new LogEvent(
                                sprintf(
                                    'File "%s" was updated by dropbox synchronisation. Data processing ID %u',
                                    $entry['path'],
                                    $this->getModel()->id
                                ),
                                __METHOD__,
                                TL_GENERAL
                            )
                        );
                    }
                }
            }
        }

        // Trigger format handler sync
        $formatHandler = $this->getModel()->getFormatHandler();
        if ($formatHandler instanceof Format\TwoWaySyncInterface) {
            $formatHandler->backSyncFiles(
                array_map(
                    function ($value) {
                        return $value[1];
                    },
                    $files
                ),
                'dropbox'
            );
        }
    }
}
