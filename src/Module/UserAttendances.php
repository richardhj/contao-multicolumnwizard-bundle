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

namespace Richardhj\ContaoFerienpassBundle\Module;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\Template;
use ContaoCommunityAlliance\UrlBuilder\UrlBuilder;
use MetaModels\Render\Setting\IRenderSettingFactory;
use Richardhj\ContaoFerienpassBundle\Event\UserAttendancesTableEvent;
use Richardhj\ContaoFerienpassBundle\Helper\Table;
use Richardhj\ContaoFerienpassBundle\Helper\ToolboxOfferDate;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;
use Richardhj\ContaoFerienpassBundle\Model\Offer;
use Richardhj\ContaoFerienpassBundle\Model\Participant;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class UserAttendances
 *
 * @package Richardhj\ContaoFerienpassBundle\Module
 */
class UserAttendances extends AbstractFrontendModuleController
{

    /**
     * The offer model.
     *
     * @var Offer
     */
    private $offerModel;

    /**
     * The participant model.
     *
     * @var Participant
     */
    private $participantModel;

    /**
     * The event dispatcher.
     *
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * The authenticated frontend user.
     *
     * @var FrontendUser
     */
    private $frontendUser;

    /**
     * The translator.
     *
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * The MetaModels render setting factory.
     *
     * @var IRenderSettingFactory
     */
    private $renderSettingFactory;

    /**
     * UserAttendances constructor.
     *
     * @param Offer                    $offerModel           The offer model.
     * @param Participant              $participantModel     The participant model.
     * @param EventDispatcherInterface $dispatcher           The event dispatcher.
     * @param TranslatorInterface      $translator           The translator.
     * @param IRenderSettingFactory    $renderSettingFactory The MetaModels render setting factory.
     */
    public function __construct(
        Offer $offerModel,
        Participant $participantModel,
        EventDispatcherInterface $dispatcher,
        TranslatorInterface $translator,
        IRenderSettingFactory $renderSettingFactory
    ) {
        $this->offerModel           = $offerModel;
        $this->participantModel     = $participantModel;
        $this->dispatcher           = $dispatcher;
        $this->translator           = $translator;
        $this->renderSettingFactory = $renderSettingFactory;
        $this->frontendUser         = FrontendUser::getInstance();
    }

    /**
     * Returns the response.
     *
     * @param Template|object $template The template.
     * @param ModuleModel     $model    The module model.
     * @param Request         $request  The request.
     *
     * @return Response
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        // Delete attendance
        if ('delete' === $request->query->get('action')) {
            $attendanceToDelete = Attendance::findByPk($request->query->get('id'));
            if (null === $attendanceToDelete) {
                $urlBuilder = UrlBuilder::fromUrl($request->getUri());
                $urlBuilder->unsetQueryParameter('action');
                $urlBuilder->unsetQueryParameter('id');

                throw new RedirectResponseException($urlBuilder->getUrl());
            }

            $urlBuilder = UrlBuilder::fromUrl($request->getUri());
            $urlBuilder->unsetQueryParameter('action');
            $urlBuilder->unsetQueryParameter('id');

            $applicationSystem = $this->offerModel->getApplicationSystem($attendanceToDelete->getOffer());
            if (null === $applicationSystem) {
                $this->addFlash('error', 'Zurzeit sind keine Anmeldungen möglich');

                throw new RedirectResponseException($urlBuilder->getUrl());
            }

            if (!$this->participantModel->isProperChild($attendanceToDelete->participant, $this->frontendUser->id)) {
                throw new AccessDeniedException('Lack of permission to delete order ID ' . $attendanceToDelete->id);
            }

            $applicationSystem->deleteAttendance($attendanceToDelete);

            $this->addFlash('confirmation', $GLOBALS['TL_LANG']['MSC']['attendanceDeletedConfirmation']);

            throw new RedirectResponseException($urlBuilder->getUrl());
        }

        $attendances = Attendance::findByParent($this->frontendUser->id);

        $items = [];
        if (null !== $attendances) {
            // Create table head
            $item = [];

            $item['row'] = [
                'offer_name'       => $this->offerModel->getMetaModel()->getAttribute('name')->getName(),
                'participant_name' => $this->participantModel->getMetaModel()->getAttribute('name')->getName(),
                'offer_date'       => $this->offerModel->getMetaModel()->getAttribute('date_period')->getName(),
                'state'            => $this->translator->trans('MSC.state', [], 'contao_default'),
                'details'          => '&nbsp;',
                'recall'           => '&nbsp;'
            ];

            $items[] = $item;

            // Walk each attendee
            while ($attendances->next()) {
                $item = ['attendance' => $attendances->current()];

                /** @var \MetaModels\Item|null $offer */
                $offer       = $item['offer'] = $attendances->current()->getOffer();
                $participant = $item['participant'] = $attendances->current()->getParticipant();
                $status      = $item['status'] = AttendanceStatus::findByPk($attendances->status);
                $view        = $item['view'] = $this->renderSettingFactory->createCollection($offer->getMetaModel(), 4);

                $detailsLink = $offer->buildJumpToLink($view)['url'];

                // Build recall link
                if (ToolboxOfferDate::offerStart($offer) >= time()) {
                    $urlBuilder = UrlBuilder::fromUrl($request->getUri())
                        ->setQueryParameter('action', 'delete')
                        ->setQueryParameter('id', $attendances->id);

                    $confirm   = sprintf(
                        $this->translator->trans('MSC.attendanceConfirmDeleteLink', [], 'contao_default'),
                        $offer->parseAttribute('name')['text'],
                        $participant->parseAttribute('name')['text']
                    );
                    $attribute =
                        'onclick="return confirm(\'' . htmlspecialchars($confirm, ENT_QUOTES | ENT_HTML5) . '\')"';

                    $minus24hours = time() - 86400;
                    $disabled     =
                        (ToolboxOfferDate::offerStart($offer) < $minus24hours) && $attendances->tstamp >= $minus24hours;

                    $recallLink = sprintf(
                        '<a href="%s" class="%s%s" %s>%s</a>',
                        !$disabled ? $urlBuilder->getUrl() : '',
                        'link--recall',
                        $disabled ? ' link--disabled' : '',
                        $attribute,
                        $this->translator->trans('MSC.recall', [], 'contao_default')
                    );
                } else {
                    $recallLink = '&nbsp;';
                }

                $item['row'] = [
                    'offer_name'       => $offer->parseAttribute('name', 'text', $view)['text'],
                    'participant_name' => $participant->parseAttribute('name')['text'],
                    'offer_date'       => $offer->parseAttribute('date_period', 'text', $view)['text'],
                    'state'            => sprintf(
                        '<span class="attendance-status attendance-status--%s">%s</span>',
                        $status->type,
                        $status->title ?: $status->name
                    ),
                    'details'          => sprintf(
                        '<a href="%s" class="link--details">%s</a>',
                        $detailsLink,
                        $this->translator->trans('MSC.details', [], 'contao_default')
                    ),
                    'recall'           => $recallLink
                ];

                $items[] = $item;
            }

            $tableEvent = new UserAttendancesTableEvent($items);
            $this->dispatcher->dispatch($tableEvent::NAME, $tableEvent);
            $items = $tableEvent->getItems();

            $rows = array_column($items, 'row');

            if (\count($rows) <= 1) {
                $template->info = $this->translator->trans('MSC.noAttendances', [], 'contao_default');
            } else {
                $template->dataTable = Table::getDataArray($rows, 'user-attendances', $model);
            }
        } else {
            $template->info = $this->translator->trans('MSC.noParticipants', [], 'contao_default');
        }
        return Response::create($template->parse());
    }
}
