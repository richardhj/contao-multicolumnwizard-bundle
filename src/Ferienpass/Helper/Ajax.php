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

use Contao\Input;
use Contao\Versions;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use Ferienpass\Model\Attendance;
use Haste\Http\Response\JsonResponse;


/**
 * Class Ajax
 * @package Ferienpass\Helper
 */
class Ajax
{

    /**
     * Handle the reposition of attendances in the backend
     *
     * @param $action
     *
     * @internal param $dc
     */
    public function handleOfferAttendancesView($action)
    {
        if ('offerAttendancesSorting' !== $action) {
            return;
        }

        try {
            $oldStatusId = ModelId::fromSerialized(Input::post('oldStatus'));
            $newStatusId = ModelId::fromSerialized(Input::post('newStatus'));
            $modelId = ModelId::fromSerialized(Input::post('model'));
            $previousModelId = ('' !== Input::post('previousModel'))
                ? ModelId::fromSerialized(Input::post('previousModel'))
                : null;

        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
            $response = new JsonResponse($response);
            $response->send();

            return;
        }

        // Check permissions
        //@todo

        // Initialize versioning
        $versions = new Versions($modelId->getDataProviderName(), $modelId->getId());
        $versions->initialize();

        $attendance = Attendance::findByPk($modelId->getId());
        $attendance->tstamp = time();
        $attendance->status = $newStatusId->getId();
        $attendance->save();

        $versions->create();

        // Update sorting
        $sortingHelper = new SortingHelper($modelId->getDataProviderName());
        $sortingHelper->setAttendanceAfter($modelId, $previousModelId);

        // Respond
        $response = [
            'success'    => true,
            'startCount' => Attendance::countByOfferAndStatus($attendance->offer, $oldStatusId->getId()),
            'endCount'   => Attendance::countByOfferAndStatus($attendance->offer, $attendance->status),
        ];
        $response = new JsonResponse($response);
        $response->send();
    }
}