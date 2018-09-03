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

namespace Richardhj\ContaoFerienpassBundle\Controller\Frontend;

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\MemberModel;
use Contao\Model;
use DateTime;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;
use MetaModels\Item;
use MetaModels\Render\Setting\IRenderSettingFactory;
use Richardhj\ContaoFerienpassBundle\Helper\ToolboxOfferDate;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

/**
 * This controller handles the export of the user's attendances to the desired format, e.g. ics.
 */
class ExportAttendances
{

    /**
     * @var IRenderSettingFactory
     */
    private $renderSettingFactory;

    /**
     * @var int
     */
    private $listViewId;

    /**
     * @var string
     */
    private $secret;

    /**
     * ExportAttendances constructor.
     *
     * @param IRenderSettingFactory $renderSettingFactory The MetaModels render setting factory.
     * @param int                   $listViewId           The id of the MetaModels render setting of the list view.
     * @param string                $secret               The secret.
     */
    public function __construct(
        IRenderSettingFactory $renderSettingFactory,
        int $listViewId,
        string $secret
    ) {
        $this->renderSettingFactory = $renderSettingFactory;
        $this->listViewId           = $listViewId;
        $this->secret               = $secret;
    }

    /**@noinspection MoreThanThreeArgumentsInspection
     *
     * @param int     $memberId The member id with the attendances to export.
     * @param string  $token    The token.
     * @param string  $_format  The desired format, e.g. ics
     * @param Request $request  The current request.
     *
     * @return Response
     */
    public function __invoke(int $memberId, string $token, string $_format, Request $request)
    {
        if (null === $member = MemberModel::findByPk($memberId)) {
            throw new PageNotFoundException('Member ID not found: ' . $memberId);
        }

        $expectedToken = hash('ripemd128', implode('', [$member->id, $_format, $this->secret]));
        $expectedToken = substr($expectedToken, 0, 8);
        if (false === hash_equals($expectedToken, $token)) {
            throw new BadCredentialsException();
        }

        if ('ics' !== $_format) {
            throw new PageNotFoundException('Format not supported: ' . $_format);
        }

        $attendances = Attendance::findByParent($memberId);

        $response = new Response($this->createICal($attendances, $request));
        $response->headers->set('Content-Type', 'text/calendar');

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'cal.ics'
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @param Model\Collection|null $attendances
     * @param Request               $request
     *
     * @return string
     */
    private function createICal($attendances, Request $request): string
    {
        $calendar = new Calendar($request->getHttpHost());
        if (null !== $attendances) {
            while ($attendances->next()) {
                /** @var Attendance $attendance */
                $attendance  = $attendances->current();
                $item        = $attendance->getOffer();
                $participant = $attendance->getParticipant();
                if (null === $item || null === $participant) {
                    continue;
                }

                $dateAttribute = ToolboxOfferDate::fetchDateAttribute($item);

                $date = $item->get($dateAttribute->getColName());
                if (null === $date) {
                    continue;
                }

                foreach ((array) $date as $period) {
                    $event = new Event();

                    $dateTime = new DateTime('@' . $period['start']);
                    $event->setDtStart($dateTime);
                    $dateTime = new DateTime('@' . $period['end']);
                    $event->setDtEnd($dateTime);

                    $event->setSummary('Ferienpass ' . $participant->get('firstname') . ': ' . $item->get('name'));

                    $dateTime = new DateTime('@' . $attendance->created);
                    $event->setCreated($dateTime);
                    $dateTime = new DateTime('@' . $attendance->tstamp);
                    $event->setModified($dateTime);

                    if ($item instanceof Item) {
                        $view =
                            $this->renderSettingFactory->createCollection($item->getMetaModel(), $this->listViewId);

                        $jumpToLink = $item->buildJumpToLink($view);
                        if (true === $jumpToLink['deep']) {
                            $url = $request->getSchemeAndHttpHost() . '/' . $jumpToLink['url'];
                            $event->setUrl($url);
                        }
                    }

                    //$event->setLocation()
                    if ($item->get('cancelled') || $attendance->getStatus() !== AttendanceStatus::findConfirmed()) {
                        $event->setCancelled(true);
                    }

                    $event->setUseTimezone(true);
                    $calendar->addComponent($event);
                }
            }
        }

        return $calendar->render();
    }
}
