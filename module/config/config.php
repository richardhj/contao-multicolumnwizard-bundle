<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
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
$GLOBALS['TL_MODELS']['tl_ferienpass_attendance'] = 'Richardhj\ContaoFerienpassBundle\Model\Attendance';
$GLOBALS['TL_MODELS']['tl_ferienpass_applicationsystem'] = 'Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem';
$GLOBALS['TL_MODELS']['tl_ferienpass_attendancestatus'] = 'Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus';
$GLOBALS['TL_MODELS']['tl_ferienpass_attendance_reminder'] = 'Richardhj\ContaoFerienpassBundle\Model\AttendanceReminder';
$GLOBALS['TL_MODELS']['tl_ferienpass_document'] = 'Richardhj\ContaoFerienpassBundle\Model\Document';
$GLOBALS['TL_MODELS']['tl_ferienpass_dataprocessing'] = 'Richardhj\ContaoFerienpassBundle\Model\DataProcessing';



/**
 * Ferienpass Modules
 */
$GLOBALS['FERIENPASS_MOD'] = [
    'tools'           => [
        'erase_member_data' => [
            'callback' => 'Richardhj\ContaoFerienpassBundle\BackendModule\EraseMemberData',
            'icon'     => 'trash-o',
            ],
        'send_member_attendances_overview' => [
            'callback' => 'Richardhj\ContaoFerienpassBundle\BackendModule\SendMemberAttendancesOverview',
            'icon'     => 'envelope-o',
        ],
    ],
    'data_processing' => [],
    'setup'           => [
        'data_processings'  => [
            'tables' => [\Richardhj\ContaoFerienpassBundle\Model\DataProcessing::getTable()],
            'icon'   => 'folder-open',
        ],
        'documents'         => [
            'tables' => [\Richardhj\ContaoFerienpassBundle\Model\Document::getTable()],
            'icon'   => 'file-text-o',
        ],
        'application_system' => [
            'tables' => [\Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem::getTable()],
            'icon'   => 'th-list',
        ],
        'attendance_status' => [
            'tables' => [\Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus::getTable()],
            'icon'   => 'th-list',
        ],
        'attendance_reminders' => [
            'tables' => [\Richardhj\ContaoFerienpassBundle\Model\AttendanceReminder::getTable()],
            'icon'   => 'clock-o',
        ],
        'ferienpass_config' => [
            'tables' => ['tl_ferienpass_config'],
            'icon'   => 'cogs',
        ],
    ],
];

if ($_GET['do'] == 'ferienpass_management') {
    foreach ($GLOBALS['FERIENPASS_MOD'] as $group => $modules) {
        if ('data_processing' === $group) {
            $processings = \Richardhj\ContaoFerienpassBundle\Model\DataProcessing::findAll();

            while (null !== $processings && $processings->next()) {
                $GLOBALS['FERIENPASS_MOD'][$group]['data_processing_'.$processings->id] = [
                    'callback' => 'Richardhj\ContaoFerienpassBundle\BackendModule\DataProcessing',
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
if (TL_MODE == 'BE') {
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
$GLOBALS['FE_MOD']['application']['offer_editing'] = 'Richardhj\ContaoFerienpassBundle\Module\Editing';
$GLOBALS['FE_MOD']['application']['items_editing_actions'] = 'Richardhj\ContaoFerienpassBundle\Module\EditingActions';
$GLOBALS['FE_MOD']['application']['offer_user_application'] = 'Richardhj\ContaoFerienpassBundle\Module\UserApplication';
$GLOBALS['FE_MOD']['application']['offer_applicationlisthost'] = 'Richardhj\ContaoFerienpassBundle\Module\ApplicationListHost';
$GLOBALS['FE_MOD']['application']['offer_addattendeehost'] = 'Richardhj\ContaoFerienpassBundle\Module\AddAttendeeHost';
$GLOBALS['FE_MOD']['application']['offers_user_attendances'] = 'Richardhj\ContaoFerienpassBundle\Module\UserAttendances';
$GLOBALS['FE_MOD']['application']['ferienpass_messages'] = 'Richardhj\ContaoFerienpassBundle\Module\Messages';
$GLOBALS['FE_MOD']['user']['host_logo'] = 'Richardhj\ContaoFerienpassBundle\Module\HostLogo';


/**
 * Content elements
 */
$GLOBALS['TL_CTE']['ferienpass']['host_editing_list'] = 'Richardhj\ContaoFerienpassBundle\Module\HostEditingList';


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
$GLOBALS['BE_FFL']['fp_age'] = 'Richardhj\ContaoFerienpassBundle\Widget\Age';
$GLOBALS['BE_FFL']['offer_date'] = 'Richardhj\ContaoFerienpassBundle\Widget\OfferDate';
$GLOBALS['BE_FFL']['request_access_token'] = 'Richardhj\ContaoFerienpassBundle\Widget\RequestAccessToken';


/**
 * Front end form fields
 */
$GLOBALS['TL_FFL']['fp_age'] = 'Richardhj\ContaoFerienpassBundle\Form\Age';
$GLOBALS['TL_FFL']['offer_date'] = 'Richardhj\ContaoFerienpassBundle\Form\OfferDate';
$GLOBALS['TL_FFL']['select_disabled_options'] = 'Richardhj\ContaoFerienpassBundle\Form\SelectDisabledOptions';
$GLOBALS['TL_FFL']['fileTree'] = 'Richardhj\ContaoFerienpassBundle\Form\UploadImage';
$GLOBALS['TL_FFL']['multiColumnWizard'] = 'Richardhj\ContaoFerienpassBundle\Form\MultiColumnWizard';
$GLOBALS['TL_FFL']['host_logo'] = 'Richardhj\ContaoFerienpassBundle\Form\HostLogo';

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['Richardhj\ContaoFerienpassBundle\Helper\InsertTags', 'replaceInsertTags'];
$GLOBALS['TL_HOOKS']['createNewUser'][] = ['Richardhj\ContaoFerienpassBundle\Helper\UserAccount', 'createNewUser'];
$GLOBALS['TL_HOOKS']['closeAccount'][] = ['Richardhj\ContaoFerienpassBundle\Helper\UserAccount', 'closeAccount'];
$GLOBALS['TL_HOOKS']['getAllEvents'][] = ['Richardhj\ContaoFerienpassBundle\Helper\Events', 'getMetaModelAsEvents'];
$GLOBALS['TL_HOOKS']['executePostActions'][] = ['Richardhj\ContaoFerienpassBundle\Helper\Ajax', 'handleOfferAttendancesView'];
$GLOBALS['TL_HOOKS']['getSystemMessages'][] = ['Richardhj\ContaoFerienpassBundle\Helper\Backend', 'addCurrentApplicationSystemToSystemMessages'];
