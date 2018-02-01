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

namespace Richardhj\ContaoFerienpassBundle\Controller\Backend;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\System;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This controller handles the dropbox webhook.
 */
class DropboxWebhook
{

    /**
     * @param Request $request The request.
     *
     * @return Response
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     * @throws AccessDeniedException
     */
    public function __invoke(Request $request)
    {
        header('Content-Type: text/plain');

        // Check get parameter
        // Necessary for enabling the webhook via dropbox' app console
        if ($challenge = $request->query->get('challenge')) {
            return Response::create($challenge);
        }

        $rawData   = file_get_contents('php://input');
        $json      = json_decode($rawData);
        $appSecret = System::getContainer()->getParameter('richardhj.ferienpass.dropbox.appSecret');

        // Check the signature for a valid request
        if ($_SERVER['HTTP_X_DROPBOX_SIGNATURE'] !== hash_hmac('sha256', $rawData, $appSecret)) {
            throw new AccessDeniedException('Dropbox signature could not be verified');
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

        return Response::create();
    }
}
