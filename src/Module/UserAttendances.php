<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\Module;

use Contao\Controller;
use Contao\Input;
use Contao\RequestToken;
use Richardhj\ContaoFerienpassBundle\Helper\Message;
use Richardhj\ContaoFerienpassBundle\Helper\Table;
use Richardhj\ContaoFerienpassBundle\Helper\ToolboxOfferDate;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;
use Richardhj\ContaoFerienpassBundle\Model\Participant;


/**
 * Class UserAttendances
 * @package Richardhj\ContaoFerienpassBundle\Module
 */
class UserAttendances extends Items
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_user_attendances';


    /**
     * {@inheritdoc}
     */
    public function generate()
    {
        // Load language file
        Controller::loadLanguageFile('exception');

        return parent::generate();
    }


    /**
     * {@inheritdoc}
     */
    protected function compile()
    {
        /*
         * Delete attendance
         */
        if ('delete' === substr(Input::get('action'), 0, 6)) {
            list(, $id, $rt) = trimsplit('::', Input::get('action'));
            $attendanceToDelete = Attendance::findByPk($id);

            // Validate request token
            if (!RequestToken::validate($rt)) {
                Message::addError($GLOBALS['TL_LANG']['XPT']['tokenRetry']);
            } // Check for existence
            elseif (null === $attendanceToDelete) {
                Message::addError($GLOBALS['TL_LANG']['XPT']['attendanceDeleteNotFound']);
            } // Check for permission
            elseif (!Participant::getInstance()->isProperChild($attendanceToDelete->participant, $this->User->id)) {
                Message::addError($GLOBALS['TL_LANG']['XPT']['attendanceDeleteMissingPermission']);
                \System::log(
                    sprintf(
                        'User "%s" does not have the permission to delete attendance ID %u',
                        $this->User->username,
                        $attendanceToDelete->id
                    ),
                    __METHOD__,
                    TL_ERROR
                );
            } // Check for offer's date
            elseif (ToolboxOfferDate::offerStart($attendanceToDelete->offer) <= time()) {
                Message::addError($GLOBALS['TL_LANG']['XPT']['attendanceDeleteOfferInPast']);
            } // Delete
            else {
                $attendanceToDelete->delete();

                Message::addConfirmation($GLOBALS['TL_LANG']['MSC']['attendanceDeletedConfirmation']);
                Controller::redirect($this->addToUrl('action='));
            }
        }

        /*
         * Create table
         */
        $attendances = Attendance::findByParent($this->User->id);

        $rows = [];
        $fields = ['offer.name', 'participant.name', 'offer.date_period', 'state', 'details', 'recall'];

        if (null !== $attendances) {
            // Create table head
            foreach ($fields as $field) {
                $f = trimsplit('.', $field);
                $key = (strpos($field, '.') !== false) ? $f[1] : $field;

                switch ($f[0]) {
                    case 'offer':
                        $rows[0][] = $this->metaModel->getAttribute($key)->getName();
                        break;

                    case 'participant':
                        $rows[0][] = Participant::getInstance()->getMetaModel()->getAttribute($key)->getName();
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
                    $item = $this->metaModel->findById($attendances->offer);

                    switch ($f[0]) {
                        case 'offer':
                            $value = $item->parseAttribute($f[1])['text'];
                            break;

                        case 'participant':
                            $value = Participant::getInstance()->findById($attendances->participant)->get($f[1]);
                            break;

                        case 'state':
                            /** @var AttendanceStatus $status */
                            $status = AttendanceStatus::findByPk($attendances->status);
                            $value = sprintf(
                                '<span class="state %s">%s</span>',
                                $status->cssClass,
                                $status->title ?: $status->name
                            );
                            break;

                        case 'details':
                            $url = $item->buildJumpToLink($this->metaModel->getView(4))['url'];//@todo make configurable
                            $attribute = ($this->openLightbox) ? ' data-lightbox="' : '';

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
                                $url = $this->addToUrl('action=delete::'.$attendances->id.'::'.REQUEST_TOKEN);
                                $attribute = ' onclick="return confirm(\''.htmlspecialchars(
                                        sprintf(
                                            $GLOBALS['TL_LANG']['MSC']['attendanceConfirmDeleteLink'],
                                            $item->parseAttribute('name')['text'],
                                            Participant::getInstance()
                                                ->findById($attendances->participant)
                                                ->parseAttribute('name')['text']
                                        )
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

            if (count($rows) <= 1) {
                Message::addInformation($GLOBALS['TL_LANG']['MSC']['noAttendances']);
            } else {
                $this->useHeader = true;
                $this->Template->dataTable = Table::getDataArray($rows, 'user-attendances', $this);
            }
        } else {
            Message::addWarning($GLOBALS['TL_LANG']['MSC']['noParticipants']);
        }

        $this->Template->message = Message::generate();
    }
}
