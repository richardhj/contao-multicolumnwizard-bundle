<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Helper;

use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\DataProcessing;
use Haste\Http\Response\JsonResponse;


/**
 * Class Ajax
 * @package Ferienpass\Helper
 */
class Ajax
{

    /**
     * Handle the Dropbox webhook
     */
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


    /**
     * Handle the reposition of attendances in the backend
     *
     * @param $action
     */
    public function handleOfferAttendancesView($action, $dc)
    {
        if ($action !== 'offerAttendancesSorting') {
            return;
        }

        try {
            $oldStatusId = ModelId::fromSerialized(\Input::post('oldStatus'));
            $newStatusId = ModelId::fromSerialized(\Input::post('newStatus'));
            $modelId = ModelId::fromSerialized(\Input::post('model'));
            $previousModelId = ('' !== \Input::post('previousModel'))
                ? ModelId::fromSerialized(\Input::post('previousModel'))
                : null;

        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'error'   => $e->getMessage(),
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
        $attendance->tstamp = time();
        $attendance->status = $newStatusId->getId();
        $attendance->save();

        $versions->create();

        // Update sorting
        SortingHelper::init($modelId->getDataProviderName())->setAttendanceAfter($modelId, $previousModelId);


        $response = [
            'success'    => true,
            'startCount' => Attendance::countByOfferAndStatus($attendance->offer, $oldStatusId->getId()),
            'endCount'   => Attendance::countByOfferAndStatus($attendance->offer, $attendance->status),
        ];
        $response = new JsonResponse($response);
        $response->send();
    }
}
