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

namespace Richardhj\ContaoFerienpassBundle\Module;

use Contao\BackendTemplate;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\FrontendUser;
use Contao\Input;
use Contao\Module;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ContaoCommunityAlliance\UrlBuilder\UrlBuilder;
use MetaModels\Render\Setting\IRenderSettingFactory;
use ModuleModel;
use Contao\System;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\LogEvent;
use Patchwork\Utf8;
use Richardhj\ContaoFerienpassBundle\Event\UserAttendancesTableEvent;
use Richardhj\ContaoFerienpassBundle\Helper\Message;
use Richardhj\ContaoFerienpassBundle\Helper\Table;
use Richardhj\ContaoFerienpassBundle\Helper\ToolboxOfferDate;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;
use Richardhj\ContaoFerienpassBundle\Model\Offer;
use Richardhj\ContaoFerienpassBundle\Model\Participant;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;


/**
 * Class UserAttendances
 *
 * @package Richardhj\ContaoFerienpassBundle\Module
 */
class UserAttendances extends Module
{

    /**
     * @var Offer
     */
    private $offerModel;

    /**
     * @var Participant
     */
    private $participantModel;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var RequestScopeDeterminator
     */
    private $scopeMatcher;

    /**
     * @var FrontendUser
     */
    private $frontendUser;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var IRenderSettingFactory
     */
    private $renderSettingFactory;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * UserAttendances constructor.
     *
     * @param ModuleModel $module
     * @param string      $column
     *
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    public function __construct(ModuleModel $module, $column = 'main')
    {
        parent::__construct($module, $column);

        $this->strTemplate          = 'mod_user_attendances';
        $this->offerModel           = System::getContainer()->get('richardhj.ferienpass.model.offer');
        $this->participantModel     = System::getContainer()->get('richardhj.ferienpass.model.participant');
        $this->dispatcher           = System::getContainer()->get('event_dispatcher');
        $this->scopeMatcher         = System::getContainer()->get('cca.dc-general.scope-matcher');
        $this->translator           = System::getContainer()->get('translator');
        $this->renderSettingFactory = System::getContainer()->get('metamodels.render_setting_factory');
        $this->requestStack         = System::getContainer()->get('request_stack');
        $this->eventDispatcher      = System::getContainer()->get('event_dispatcher');
        $this->frontendUser         = FrontendUser::getInstance();
    }

    /**
     * {@inheritdoc}
     */
    public function generate(): string
    {
        if ($this->scopeMatcher->currentScopeIsBackend()) {
            $template = new BackendTemplate('be_wildcard');

            $template->wildcard = '### ' . Utf8::strtoupper($GLOBALS['TL_LANG']['FMD'][$this->type][0]) . ' ###';
            $template->title    = $this->headline;
            $template->id       = $this->id;
            $template->link     = $this->name;
            $template->href     = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $template->parse();
        }

        // Set a custom template
        if ('' !== $this->customTpl) {
            $this->strTemplate = $this->customTpl;
        }

        return parent::generate();
    }


    /**
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     * @throws InvalidArgumentException
     */
    protected function compile(): void
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        // Delete attendance
        if ('delete' === $request->query->get('action')) {
            $attendanceToDelete = Attendance::findByPk($request->query->get('id'));
            if (null === $attendanceToDelete) {
                // Check for existence
                Message::addError('Anmeldung schon gelöscht');
            } elseif (!$this->participantModel->isProperChild(
                $attendanceToDelete->participant,
                $this->frontendUser->id
            )) {
                // Check for permission
                Message::addError('keine Berechtigung');
                $this->dispatcher->dispatch(
                    new LogEvent(
                        sprintf(
                            'User "%s" does not have the permission to delete attendance ID %u',
                            $this->frontendUser->username,
                            $attendanceToDelete->id
                        ),
                        __METHOD__,
                        TL_ERROR
                    )
                );
            } elseif (ToolboxOfferDate::offerStart($attendanceToDelete->offer) <= time()) {
                // Check for offer's date
                Message::addError($GLOBALS['TL_LANG']['XPT']['attendanceDeleteOfferInPast']);
            } else {
                // Delete
                $attendanceToDelete->delete();

                Message::addConfirmation($GLOBALS['TL_LANG']['MSC']['attendanceDeletedConfirmation']);

                $urlBuilder = UrlBuilder::fromUrl($request->getUri());
                $urlBuilder->unsetQueryParameter('action');
                $urlBuilder->unsetQueryParameter('id');

                throw new RedirectResponseException($urlBuilder->getUrl());
            }
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
                Message::addInformation($this->translator->trans('MSC.noAttendances', [], 'contao_default'));
            } else {
                $this->useHeader           = true;
                $this->Template->dataTable = Table::getDataArray($rows, 'user-attendances', $this);
            }
        } else {
            Message::addWarning($this->translator->trans('MSC.noParticipants', [], 'contao_default'));
        }

        $this->Template->message = Message::generate();
    }
}
