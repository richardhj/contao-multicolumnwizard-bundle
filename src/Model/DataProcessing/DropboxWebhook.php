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

namespace Richardhj\ContaoFerienpassBundle\Model\DataProcessing;


use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;

class DropboxWebhook
{

    /**
     * Handle the dropbox webhook request
     */
    public function handle()
    {
        global $container;

        header('Content-Type: text/plain');

        // Check get parameter
        // Necessary for enabling the webhook via dropbox' app console
        if (($challenge = \Input::get('challenge'))) {
            die($challenge);
        }

        $rawData   = file_get_contents('php://input');
        $json      = json_decode($rawData);
        $appSecret = $container['ferienpass.dropbox.appSecret'];

        // Check the signature for a valid request
        if ($_SERVER['HTTP_X_DROPBOX_SIGNATURE'] !== hash_hmac('sha256', $rawData, $appSecret)) {
            header('HTTP/1.0 403 Forbidden');
            die('Invalid request');
        }

        // Return a response to the client before processing
        // Dropbox wants a response quickly
        header('Connection: close');
        ob_start();
        header('HTTP/1.0 200 OK');
        ob_end_flush();
        flush();

        // Fetch all dropbox data processings with the submitted UIDs
        /** @type \Model\Collection $processings */
        $processings = DataProcessing::findBy(
            [
                "filesystem='dropbox'",
                'sync=1',
                sprintf(
                    'dropbox_uid IN(%s)',
                    implode(',', array_map('intval', $json->delta->users))
                ),
            ],
            null
        );

        // Walk each data processing
        /** @var DataProcessing|\Model\Collection $processings */
        while (null !== $processings && $processings->next()) {
            $fileSystemHandler = $processings->current()->getFileSystemHandler();

            if ($fileSystemHandler instanceof DataProcessing\Filesystem\TwoWaySyncInterface) {
                $fileSystemHandler->triggerBackSync();
            }
        }
    }
}
