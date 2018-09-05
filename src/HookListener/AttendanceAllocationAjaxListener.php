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

namespace Richardhj\ContaoFerienpassBundle\HookListener;

use Contao\Versions;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\DcGeneral\Exception\DcGeneralRuntimeException;
use ContaoCommunityAlliance\Translator\TranslatorInterface as CcaTranslator;
use Richardhj\ContaoFerienpassBundle\Helper\SortingHelper;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;


/**
 * Class AttendanceAllocationAjaxListener
 *
 * @package Richardhj\ContaoFerienpassBundle\HookListener
 */
class AttendanceAllocationAjaxListener
{
    /**
     * The request stack.
     *
     * @var RequestStack
     */
    private $requestStack;

    /**
     * The event dispatcher.
     *
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * The backported translator.
     *
     * @var CcaTranslator
     */
    private $translator;

    /**
     * AttendanceAllocationAjaxListener constructor.
     *
     * @param RequestStack             $requestStack The request stack.
     * @param EventDispatcherInterface $dispatcher   The event dispatcher.
     * @param CcaTranslator            $translator   The backported translator.
     */
    public function __construct(
        RequestStack $requestStack,
        EventDispatcherInterface $dispatcher,
        CcaTranslator $translator
    ) {
        $this->requestStack = $requestStack;
        $this->dispatcher   = $dispatcher;
        $this->translator   = $translator;
    }

    /**
     * Handle the reposition of attendances in the backend.
     *
     * @param string $action The action name.
     */
    public function onExecutePostActions($action): void
    {
        if ('mm_ferienpass_attendance_allocation' !== $action) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return;
        }

        try {
            $oldStatusId     = ModelId::fromSerialized($request->request->get('oldStatus'));
            $newStatusId     = ModelId::fromSerialized($request->request->get('newStatus'));
            $modelId         = ModelId::fromSerialized($request->request->get('model'));
            $previousModelId = ('' !== $request->request->get('previousModel'))
                ? ModelId::fromSerialized($request->request->get('previousModel'))
                : null;

        } catch (DcGeneralRuntimeException $e) {
            $response = [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
            $response = new JsonResponse($response);
            $response->send();

            return;
        }

        $versions = new Versions($modelId->getDataProviderName(), $modelId->getId());
        $versions->initialize();

        $attendance = Attendance::findByPk($modelId->getId());

        $attendance->tstamp = time();
        $attendance->status = $newStatusId->getId();
        $attendance->save();

        $versions->create();

        $sortingHelper = new SortingHelper($modelId->getDataProviderName(), $this->dispatcher, $this->translator);
        $sortingHelper->setAttendanceAfter($modelId, $previousModelId);

        $response = [
            'success'    => true,
            'startCount' => Attendance::countByOfferAndStatus($attendance->offer, $oldStatusId->getId()),
            'endCount'   => Attendance::countByOfferAndStatus($attendance->offer, $attendance->status),
        ];
        $response = new JsonResponse($response);
        $response->send();
    }
}
