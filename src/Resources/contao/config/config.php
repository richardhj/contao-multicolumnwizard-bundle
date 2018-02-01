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

use Richardhj\ContaoFerienpassBundle\Helper\Backend;
use Richardhj\ContaoFerienpassBundle\Helper\Ajax;
use Richardhj\ContaoFerienpassBundle\Helper\InsertTags;
use Richardhj\ContaoFerienpassBundle\Form\MultiColumnWizard;
use Richardhj\ContaoFerienpassBundle\Form\UploadImage;
use Richardhj\ContaoFerienpassBundle\Form\SelectDisabledOptions;
use Richardhj\ContaoFerienpassBundle\Form\OfferDate;
use Richardhj\ContaoFerienpassBundle\Widget\RequestAccessToken;
use Richardhj\ContaoFerienpassBundle\Widget\Age;
use Richardhj\ContaoFerienpassBundle\Module\HostEditingList;
use Richardhj\ContaoFerienpassBundle\Module\HostLogo;
use Richardhj\ContaoFerienpassBundle\Module\Messages;
use Richardhj\ContaoFerienpassBundle\Module\UserAttendances;
use Richardhj\ContaoFerienpassBundle\Module\AddAttendeeHost;
use Richardhj\ContaoFerienpassBundle\Module\ApplicationListHost;
use Richardhj\ContaoFerienpassBundle\Module\UserApplication;
use Richardhj\ContaoFerienpassBundle\Module\EditingActions;
use Richardhj\ContaoFerienpassBundle\Module\Editing;
use Richardhj\ContaoFerienpassBundle\BackendModule\SendMemberAttendancesOverview;
use Richardhj\ContaoFerienpassBundle\BackendModule\EraseMemberData;
use Richardhj\ContaoFerienpassBundle\Model\DataProcessing;
use Richardhj\ContaoFerienpassBundle\Model\Document;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceReminder;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;
use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

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


/**
 * Back end modules
 */
array_insert(
    $GLOBALS['BE_MOD'],
    1,
    [
        'ferienpass' => [
            'ferienpass_management'  => [
                'callback' => 'Richardhj\ContaoFerienpassBundle\BackendModule\Management',
                'tables'   => [],
                'icon'     => 'assets/ferienpass/core/img/equalizer.png',
            ],
            'ferienpass_attendances' => [
                'tables' => [
                    Richardhj\ContaoFerienpassBundle\Model\Attendance::getTable(),
                    'mm_ferienpass',
                ],
                'icon'   => 'assets/ferienpass/core/img/equalizer.png',
            ],
        ],
    ]
);


/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_ferienpass_attendance'] = Attendance::class;
$GLOBALS['TL_MODELS']['tl_ferienpass_applicationsystem'] = ApplicationSystem::class;
$GLOBALS['TL_MODELS']['tl_ferienpass_attendancestatus'] = AttendanceStatus::class;
$GLOBALS['TL_MODELS']['tl_ferienpass_attendance_reminder'] = AttendanceReminder::class;
$GLOBALS['TL_MODELS']['tl_ferienpass_document'] = Document::class;
$GLOBALS['TL_MODELS']['tl_ferienpass_dataprocessing'] = DataProcessing::class;


/**
 * Ferienpass Modules
 */
$GLOBALS['FERIENPASS_MOD'] = [
    'tools'           => [
        'erase_member_data' => [
            'callback' => EraseMemberData::class,
            'icon'     => 'trash-o',
            ],
        'send_member_attendances_overview' => [
            'callback' => SendMemberAttendancesOverview::class,
            'icon'     => 'envelope-o',
        ],
    ],
    'data_processing' => [],
    'setup'           => [
        'data_processings'  => [
            'tables' => [DataProcessing::getTable()],
            'icon'   => 'folder-open',
        ],
        'documents'         => [
            'tables' => [Document::getTable()],
            'icon'   => 'file-text-o',
        ],
        'application_system' => [
            'tables' => [ApplicationSystem::getTable()],
            'icon'   => 'th-list',
        ],
        'attendance_status' => [
            'tables' => [AttendanceStatus::getTable()],
            'icon'   => 'th-list',
        ],
        'attendance_reminders' => [
            'tables' => [AttendanceReminder::getTable()],
            'icon'   => 'clock-o',
        ],
        'ferienpass_config' => [
            'tables' => ['tl_ferienpass_config'],
            'icon'   => 'cogs',
        ],
    ],
];

if ('ferienpass_management' === $_GET['do']) {
    foreach ($GLOBALS['FERIENPASS_MOD'] as $group => $modules) {
        if ('data_processing' === $group) {
            $processings = DataProcessing::findAll();

            while (null !== $processings && $processings->next()) {
                $GLOBALS['FERIENPASS_MOD'][$group]['data_processing_'.$processings->id] = [
                    'callback' => \Richardhj\ContaoFerienpassBundle\BackendModule\DataProcessing::class,
                    'icon'     => 'file-archive-o',
                ];
            }
        }


        foreach ($modules as $module => $config) {
            // Enable tables in ferienpass_setup
            if (is_array($config['tables'])) {
                $GLOBALS['BE_MOD']['ferienpass']['ferienpass_management']['tables'] = array_merge(
                    $GLOBALS['BE_MOD']['ferienpass']['ferienpass_management']['tables'],
                    $config['tables']
                );
            }
        }
    }
}


/**
 * Back end styles
 */
if ('BE' === TL_MODE) {
    $GLOBALS['TL_CSS'][] = 'assets/ferienpass/core/css/backend.css|static';
    $GLOBALS['TL_CSS'][] = 'assets/ferienpass/core/css/be_mm_ferienpass.css|static';
    $GLOBALS['TL_JAVASCRIPT'][] = 'assets/ferienpass/core/js/be_mm_ferienpass.js|static';
}


/**
 * Permissions are access settings for user and groups (fields in tl_user and tl_user_group)
 */
//@todo $GLOBALS['TL_PERMISSIONS'][] = 'iso_modules';


/**
 * Front end modules
 */
$GLOBALS['FE_MOD']['application']['offer_editing'] = Editing::class;
$GLOBALS['FE_MOD']['application']['items_editing_actions'] = EditingActions::class;
$GLOBALS['FE_MOD']['application']['offer_user_application'] = UserApplication::class;
$GLOBALS['FE_MOD']['application']['offer_applicationlisthost'] = ApplicationListHost::class;
$GLOBALS['FE_MOD']['application']['offer_addattendeehost'] = AddAttendeeHost::class;
$GLOBALS['FE_MOD']['application']['offers_user_attendances'] = UserAttendances::class;
$GLOBALS['FE_MOD']['application']['ferienpass_messages'] = Messages::class;
$GLOBALS['FE_MOD']['user']['host_logo'] = HostLogo::class;


/**
 * Content elements
 */
$GLOBALS['TL_CTE']['ferienpass']['host_editing_list'] = HostEditingList::class;


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
$GLOBALS['BE_FFL']['fp_age'] = Age::class;
$GLOBALS['BE_FFL']['offer_date'] = 'Richardhj\ContaoFerienpassBundle\Widget\OfferDate';
$GLOBALS['BE_FFL']['request_access_token'] = RequestAccessToken::class;


/**
 * Front end form fields
 */
$GLOBALS['TL_FFL']['fp_age'] = \Richardhj\ContaoFerienpassBundle\Form\Age::class;
$GLOBALS['TL_FFL']['offer_date'] = OfferDate::class;
$GLOBALS['TL_FFL']['select_disabled_options'] = SelectDisabledOptions::class;
$GLOBALS['TL_FFL']['fileTree'] = UploadImage::class;
$GLOBALS['TL_FFL']['multiColumnWizard'] = MultiColumnWizard::class;
$GLOBALS['TL_FFL']['host_logo'] = \Richardhj\ContaoFerienpassBundle\Form\HostLogo::class;

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = [InsertTags::class, 'replaceInsertTags'];
$GLOBALS['TL_HOOKS']['executePostActions'][] = [Ajax::class, 'handleOfferAttendancesView'];
$GLOBALS['TL_HOOKS']['getSystemMessages'][] = [Backend::class, 'addCurrentApplicationSystemToSystemMessages'];
