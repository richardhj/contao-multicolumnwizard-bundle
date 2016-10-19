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

use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\DataProcessing;
use Haste\Http\Response\JsonResponse;


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


    public function handleOfferAttendancesView($action)
    {
        if ($action !== 'offerAttendancesSorting') {
            return;
        }

        try {
            $modelId = ModelId::fromSerialized(\Input::post('model'));
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
            $response = new JsonResponse($response);
            $response->send();
            exit;
        }

        if (0 === ($newStatusId = (int)\Input::post('newStatusId'))) {
            $response = [
                'success' => false,
                'error'   => 'Error with newStatusId',
            ];
            $response = new JsonResponse($response);
            $response->send();
            exit;
        }

        // Check permissions
        //@todo

        // Initialize versioning
        $versions = new \Versions($modelId->getDataProviderName(), $modelId->getId());
        $versions->initialize();

        $attendance = Attendance::findByPk($modelId->getId());
        $attendance->status = $newStatusId;
        $attendance->save();

        $versions->create();

        $response = [
            'success'    => true,
            'startCount' => Attendance::countByOfferAndStatus($attendance->offer, (int)\Input::post('oldStatusId')),
            'endCount'   => Attendance::countByOfferAndStatus($attendance->offer, $attendance->status),
        ];
        $response = new JsonResponse($response);
        $response->send();
    }
}
