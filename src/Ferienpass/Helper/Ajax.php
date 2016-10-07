<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Helper;

use Ferienpass\Model\DataProcessing;


class Ajax
{

    public function handleDropboxWebhook()
    {
        if (!\Environment::get('isAjaxRequest') || 'dropbox-webhook' !== \Input::get('action')) {
            return;
        }

        /** @type \Model\Collection $processings */
        $processings = DataProcessing::findBy
        (
            [
                'dropbox_uid=?',
                'sync=1',
            ],
            [
                \Input::get('uid'),
            ]
        );

        /** @var DataProcessing $processing */
        foreach ($processings as $processing) {
            $processing->syncFromRemoteDropbox();
        }
    }
}
