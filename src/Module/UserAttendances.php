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
use Contao\Controller;
use Contao\FrontendUser;
use Contao\Input;
use Contao\Module;
use ContaoCommunityAlliance\DcGeneral\Contao\RequestScopeDeterminator;
use ModuleModel;
use Contao\System;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\LogEvent;
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
use Symfony\Component\Security\Csrf\CsrfToken;


/**
 * Class UserAttendances
 *
 * @package Richardhj\ContaoFerienpassBundle\Module
 */
class UserAttendances extends Module
{

    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'mod_user_attendances';

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

        $this->offerModel       = System::getContainer()->get('richardhj.ferienpass.model.offer');
        $this->participantModel = System::getContainer()->get('richardhj.ferienpass.model.participant');
        $this->dispatcher       = System::getContainer()->get('event_dispatcher');
        $this->scopeMatcher     = System::getContainer()->get('cca.dc-general.scope-matcher');
        $this->frontendUser     = FrontendUser::getInstance();
    }

    /**
     * {@inheritdoc}
     */
    public function generate(): string
    {
        if ($this->scopeMatcher->currentScopeIsBackend()) {
            $template = new BackendTemplate('be_wildcard');

            $template->wildcard = '### '.utf8_strtoupper($GLOBALS['TL_LANG']['FMD'][$this->type][0]).' ###';
            $template->title    = $this->headline;
            $template->id       = $this->id;
            $template->link     = $this->name;
            $template->href     = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

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
    protected function compile()
    {
        /*
         * Delete attendance
         */
        if (0 === strpos(Input::get('action'), 'delete')) {
            list(, $id, $rt) = trimsplit('::', Input::get('action'));
            $attendanceToDelete = Attendance::findByPk($id);

            if (!System::getContainer()->get('security.csrf.token_manager')->isTokenValid(
            // Validate request token
                new CsrfToken(System::getContainer()->getParameter('contao.csrf_token_name'), $rt)
            )) {
                Message::addError($GLOBALS['TL_LANG']['XPT']['tokenRetry']);
            } elseif (null === $attendanceToDelete) {
                // Check for existence
                Message::addError($GLOBALS['TL_LANG']['XPT']['attendanceDeleteNotFound']);
            } elseif (!$this->participantModel->isProperChild(
                $attendanceToDelete->participant,
                $this->frontendUser->id
            )) {
                // Check for permission
                Message::addError($GLOBALS['TL_LANG']['XPT']['attendanceDeleteMissingPermission']);
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
                Controller::redirect(static::addToUrl('action='));
            }
        }

        /*
         * Create table
         */
        $attendances = Attendance::findByParent($this->frontendUser->id);

        $rows   = [];
        $fields = [
            'offer.name',
            'participant.name',
            /*'offer.date_period',*/
            'state',
            'details',
            'recall',
        ];

        if (null !== $attendances) {
            // Create table head
            foreach ($fields as $field) {
                $f   = trimsplit('.', $field);
                $key = (strpos($field, '.') !== false) ? $f[1] : $field;

                switch ($f[0]) {
                    case 'offer':
                        $rows[0][] = $this->offerModel->getMetaModel()->getAttribute($key)->getName();
                        break;

                    case 'participant':
                        $rows[0][] = $this->participantModel->getMetaModel()->getAttribute($key)->getName();
                        break;

                    case 'details':
                    case 'recall':
                        $rows[0][] = '&nbsp;';
                        break;

                    default:
                        $rows[0][] = $GLOBALS['TL_LANG']['MSC'][$key];
                        break;
                }
            }

            // Walk each attendee
            while ($attendances->next()) {
                $values = [];

                foreach ($fields as $field) {
                    $f = trimsplit('.', $field);
                    /** @var \MetaModels\Item $item */
                    $item = $this->offerModel->getMetaModel()->findById($attendances->offer);

                    switch ($f[0]) {
                        case 'offer':
                            $value = $item->parseAttribute($f[1])['text'];
                            break;

                        case 'participant':
                            $value = $this->participantModel->findById($attendances->participant)->get($f[1]);
                            break;

                        case 'state':
                            /** @var AttendanceStatus $status */
                            $status = AttendanceStatus::findByPk($attendances->status);
                            $value  = sprintf(
                                '<span class="state %s">%s</span>',
                                $status->cssClass,
                                $status->title ?: $status->name
                            );
                            break;

                        case 'details':
                            $url       =
                                $item->buildJumpToLink(
                                    $this->offerModel->getMetaModel()->getView(4)
                                )['url'];//@todo make configurable
                            $attribute = $this->openLightbox ? ' data-lightbox="' : '';

                            $value = sprintf(
                                '<a href="%s" class="%s"%s>%s</a>',
                                $url,
                                $f[0],
                                $attribute,
                                $GLOBALS['TL_LANG']['MSC'][$f[0]]
                            );
                            break;

                        case 'recall':
                            if (ToolboxOfferDate::offerStart($item) >= time()) {
                                $url       = static::addToUrl('action=delete::'.$attendances->id.'::'.REQUEST_TOKEN);
                                $attribute = ' onclick="return confirm(\''.htmlspecialchars(
                                        sprintf(
                                            $GLOBALS['TL_LANG']['MSC']['attendanceConfirmDeleteLink'],
                                            $item->parseAttribute('name')['text'],
                                            $this->participantModel
                                                ->findById($attendances->participant)
                                                ->parseAttribute('name')['text']
                                        ),
                                        ENT_QUOTES | ENT_HTML5
                                    )
                                             .'\')"';

                                $value = sprintf(
                                    '<a href="%s" class="%s"%s>%s</a>',
                                    $url,
                                    $f[0],
                                    $attribute,
                                    $GLOBALS['TL_LANG']['MSC'][$f[0]]
                                );
                            } else {
                                $value = '';
                            }
                            break;

                        default:
                            $value = $attendances->$f[1];
                            break;
                    }

                    $values[] = $value;
                }

                $rows[] = $values;
            }

            if (\count($rows) <= 1) {
                Message::addInformation($GLOBALS['TL_LANG']['MSC']['noAttendances']);
            } else {
                $this->useHeader           = true;
                $this->Template->dataTable = Table::getDataArray($rows, 'user-attendances', $this);
            }
        } else {
            Message::addWarning($GLOBALS['TL_LANG']['MSC']['noParticipants']);
        }

        $this->Template->message = Message::generate();
    }
}
