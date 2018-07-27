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

use Richardhj\ContaoFerienpassBundle\Form\SelectDisabledOptions;
use Richardhj\ContaoFerienpassBundle\Form\OfferDate;
use Richardhj\ContaoFerienpassBundle\Widget\RequestAccessToken;
use Richardhj\ContaoFerienpassBundle\Widget\Age;
use Richardhj\ContaoFerienpassBundle\Module\HostLogo;
use Richardhj\ContaoFerienpassBundle\BackendModule\SendMemberAttendancesOverview;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceReminder;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;
use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;


/**
 * Back end modules
 */
array_insert(
    $GLOBALS['BE_MOD']['ferienpass'],
    0,
    [
        'ferienpass_attendances'                   => [
            'tables' => [
                'tl_ferienpass_attendance',
                'mm_ferienpass',
            ],
        ],
        'ferienpass_send_attendance_confirmations' => [
            'callback' => SendMemberAttendancesOverview::class,
        ],
        'ferienpass_data_processings'              => [
            'tables' => [
                'tl_ferienpass_dataprocessing',
            ],
        ],
        'ferienpass_application_systems'           => [
            'tables' => [
                'tl_ferienpass_applicationsystem',
            ],
        ],
        'ferienpass_attendance_status'             => [
            'tables' => [
                'tl_ferienpass_attendancestatus',
            ],
        ],
        'ferienpass_attendance_reminders'          => [
            'tables' => [
                'tl_ferienpass_attendance_reminder',
            ],
        ],
    ]
);


/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_ferienpass_attendance']          = Attendance::class;
$GLOBALS['TL_MODELS']['tl_ferienpass_applicationsystem']   = ApplicationSystem::class;
$GLOBALS['TL_MODELS']['tl_ferienpass_attendancestatus']    = AttendanceStatus::class;
$GLOBALS['TL_MODELS']['tl_ferienpass_attendance_reminder'] = AttendanceReminder::class;
$GLOBALS['TL_MODELS']['tl_ferienpass_dataprocessing']      = DataProcessing::class;


/**
 * Back end styles
 */
if ('BE' === TL_MODE) {
    $GLOBALS['TL_CSS'][]        = 'bundles/richardhjcontaoferienpass/css/backend.css';
    $GLOBALS['TL_CSS'][]        = 'bundles/richardhjcontaoferienpass/css/be_mm_ferienpass.css';
    $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/richardhjcontaoferienpass/js/be_mm_ferienpass.js';
}

/**
 * Front end modules
 */
$GLOBALS['FE_MOD']['user']['host_logo']                        = HostLogo::class;

/**
 * Notification center
 */
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE'] = array_merge(
    (array)$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE'],
    [
        'ferienpass' => [
            'application_list_status_change' => [
                'recipients'            => [
                    'participant_email',
                    'host_email',
                    'admin_email',
                    'member_email',
                ],
                'sms_recipients'        => [
                    'member_mobile',
                    'member_phone',
                    'participant_mobile',
                    'participant_phone',
                ],
                'email_text'            => [
                    'offer_*',
                    'participant_*',
                    'member_*',
                ],
                'email_html'            => [
                    'offer_*',
                    'participant_*',
                    'member_*',
                ],
                'email_sender_name'     => [
                    'admin_email',
                ],
                'email_sender_address'  => [
                    'admin_email',
                ],
                'email_recipient_cc'    => [
                    'admin_email',
                ],
                'email_recipient_bcc'   => [
                    'admin_email',
                ],
                'email_replyTo'         => [
                    'admin_email',
                ],
                'sms_text'              => [
                    'offer_*',
                    'participant_*',
                    'member_*',
                ],
                'sms_recipients_region' => [
                    'participant_country',
                    'member_country',
                ],
            ],
            'application_list_reminder'      => [
                'recipients'            => [
                    'participant_email',
                    'host_email',
                    'admin_email',
                    'member_email',
                ],
                'sms_recipients'        => [
                    'member_mobile',
                    'member_phone',
                    'participant_mobile',
                    'participant_phone',
                ],
                'email_text'            => [
                    'offer_*',
                    'participant_*',
                    'member_*',
                ],
                'email_html'            => [
                    'offer_*',
                    'participant_*',
                    'member_*',
                ],
                'email_sender_name'     => [
                    'admin_email',
                ],
                'email_sender_address'  => [
                    'admin_email',
                ],
                'email_recipient_cc'    => [
                    'admin_email',
                ],
                'email_recipient_bcc'   => [
                    'admin_email',
                ],
                'email_replyTo'         => [
                    'admin_email',
                ],
                'sms_text'              => [
                    'offer_*',
                    'participant_*',
                    'member_*',
                ],
                'sms_recipients_region' => [
                    'participant_country',
                    'member_country',
                ],
            ],
            'applications_member_overview'   => [
                'recipients'            => [
                    'admin_email',
                    'member_email',
                ],
                'sms_recipients'        => [
                    'member_mobile',
                    'member_phone',
                ],
                'email_text'            => [
                    'applications_text',
                    'member_*',
                ],
                'email_html'            => [
                    'applications_text',
                    'applications_html',
                    'member_*',
                ],
                'email_sender_name'     => [
                    'admin_email',
                ],
                'email_sender_address'  => [
                    'admin_email',
                ],
                'email_recipient_cc'    => [
                    'admin_email',
                ],
                'email_recipient_bcc'   => [
                    'admin_email',
                ],
                'email_replyTo'         => [
                    'admin_email',
                ],
                'sms_text'              => [
                    'applications_text',
                    'member_*',
                ],
                'sms_recipients_region' => [
                    'member_country',
                ],
            ],
        ],
    ]
);


/**
 * Back end form fields
 */
$GLOBALS['BE_FFL']['fp_age']               = Age::class;
$GLOBALS['BE_FFL']['offer_date']           = \Richardhj\ContaoFerienpassBundle\Widget\OfferDate::class;
$GLOBALS['BE_FFL']['request_access_token'] = RequestAccessToken::class;


/**
 * Front end form fields
 */
$GLOBALS['TL_FFL']['fp_age']                  = \Richardhj\ContaoFerienpassBundle\Form\Age::class;
$GLOBALS['TL_FFL']['offer_date']              = OfferDate::class;
$GLOBALS['TL_FFL']['select_disabled_options'] = SelectDisabledOptions::class;
