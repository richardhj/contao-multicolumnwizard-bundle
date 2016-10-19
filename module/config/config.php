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
                'callback' => 'Ferienpass\BackendModule\Management',
                'tables'   => [],
                'icon'     => 'assets/ferienpass/backend/img/equalizer.png',
            ],
            'ferienpass_attendances' => [
                'tables' => [
                    Ferienpass\Model\Attendance::getTable(),
                    'mm_ferienpass',
                ],
                'icon'   => 'assets/ferienpass/backend/img/equalizer.png',
            ],
        ],
    ]
);


/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_ferienpass_attendance'] = 'Ferienpass\Model\Attendance';
$GLOBALS['TL_MODELS']['tl_ferienpass_attendancestatus'] = 'Ferienpass\Model\AttendanceStatus';
$GLOBALS['TL_MODELS']['tl_ferienpass_document'] = 'Ferienpass\Model\Document';
$GLOBALS['TL_MODELS']['tl_ferienpass_dataprocessing'] = 'Ferienpass\Model\DataProcessing';


/**
 * (Local)config
 */
$GLOBALS['TL_CONFIG']['dropbox_ferienpass_appId'] = 'sf04yiig0hbwzmi';
$GLOBALS['TL_CONFIG']['dropbox_ferienpass_appSecret'] = '9gn0gckd2yr0fy9';


/**
 * Ferienpass Modules
 */
$GLOBALS['FERIENPASS_MOD'] = [
    'tools'           => [
        'erase_member_data' => [
            'callback' => 'Ferienpass\BackendModule\EraseMemberData',
            'icon'     => 'trash-o',
            ],
    ],
    'data_processing' => [],
    'setup'           => [
        'data_processings'  => [
            'tables' => [\Ferienpass\Model\DataProcessing::getTable()],
            'icon'   => 'folder-open',
        ],
        'documents'         => [
            'tables' => [\Ferienpass\Model\Document::getTable()],
            'icon'   => 'file-text-o',
        ],
        'attendance_status' => [
            'tables' => [\Ferienpass\Model\AttendanceStatus::getTable()],
            'icon'   => 'th-list',
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
            $processings = \Ferienpass\Model\DataProcessing::findAll();

            while (null !== $processings && $processings->next()) {
                $GLOBALS['FERIENPASS_MOD'][$group]['data_processing_'.$processings->id] = [
                    'callback' => 'Ferienpass\BackendModule\DataProcessing',
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
 * Ferienpass status
 */
$GLOBALS['FERIENPASS_STATUS'] = [
    'confirmed',
    'waitlisted',
    'waiting',
    'error',
];


/**
 * Back end styles
 */
if (TL_MODE == 'BE') {
    $GLOBALS['TL_CSS'][] = 'assets/ferienpass/backend/css/backend.css|static';
    $GLOBALS['TL_CSS'][] = 'assets/ferienpass/backend/css/be_mm_ferienpass.css|static';
    $GLOBALS['TL_JAVASCRIPT'][] = 'assets/ferienpass/backend/js/be_mm_ferienpass.js|static';
}


/**
 * Permissions are access settings for user and groups (fields in tl_user and tl_user_group)
 */
//@todo $GLOBALS['TL_PERMISSIONS'][] = 'iso_modules';


/**
 * Front end modules
 */
// Host overview and editing
$GLOBALS['FE_MOD']['application']['offers_management'] = 'Ferienpass\Module\Items\Offers\Management';
$GLOBALS['FE_MOD']['application']['offer_editing'] = 'Ferienpass\Module\Items\Editing';
$GLOBALS['FE_MOD']['application']['items_editing_actions'] = 'Ferienpass\Module\Items\EditingActions';

// Application list
$GLOBALS['FE_MOD']['application']['offer_applicationlist'] = 'Ferienpass\Module\Item\Offer\ApplicationList';
$GLOBALS['FE_MOD']['application']['offer_applicationlisthost'] = 'Ferienpass\Module\Item\Offer\ApplicationListHost';
$GLOBALS['FE_MOD']['application']['offer_addattendeehost'] = 'Ferienpass\Module\Item\Offer\AddAttendeeHost';

$GLOBALS['FE_MOD']['application']['offers_user_attendances'] = 'Ferienpass\Module\Items\Offers\UserAttendances';
$GLOBALS['FE_MOD']['application']['ferienpass_messages'] = 'Ferienpass\Module\Messages';

/**
 * Notification center
 */
$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE'] = array_merge(
    (array)$GLOBALS['NOTIFICATION_CENTER']['NOTIFICATION_TYPE'],
    [
        'ferienpass' => [
            'offer_al_status_change' => [
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
        ],
    ]
);


/**
 * Back end form fields
 */
$GLOBALS['BE_FFL']['fp_age'] = 'Ferienpass\Widget\Age';
$GLOBALS['BE_FFL']['request_access_token'] = 'Ferienpass\Widget\RequestAccessToken';


/**
 * Front end form fields
 */
$GLOBALS['TL_FFL']['fp_age'] = 'Ferienpass\Form\Age';
$GLOBALS['TL_FFL']['select_disabled_options'] = 'Ferienpass\Form\SelectDisabledOptions';
$GLOBALS['TL_FFL']['fileTree'] = 'Ferienpass\Form\UploadImage';
$GLOBALS['TL_FFL']['multiColumnWizard'] = 'Ferienpass\Form\MultiColumnWizard';

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['Ferienpass\Helper\InsertTags', 'replaceInsertTags'];
$GLOBALS['TL_HOOKS']['createNewUser'][] = ['Ferienpass\Helper\UserAccount', 'createNewUser'];
$GLOBALS['TL_HOOKS']['closeAccount'][] = ['Ferienpass\Helper\UserAccount', 'closeAccount'];
$GLOBALS['TL_HOOKS']['getAllEvents'][] = ['Ferienpass\Helper\Events', 'getMetaModelAsEvents'];
$GLOBALS['TL_HOOKS']['simpleAjax'][] = ['Ferienpass\Helper\Ajax', 'handleDropboxWebhook'];
$GLOBALS['TL_HOOKS']['executePostActions'][] = ['Ferienpass\Helper\Ajax', 'handleOfferAttendancesView'];

//$GLOBALS['TL_DCA'][FerienpassConfig::get(FerienpassConfig::PARTICIPANT_MODEL)]['list']['label']['label_callback'] = array('Ferienpass\Helper\Dca', 'test');

/**
 * Cron jobs
 */
//$GLOBALS['TL_CRON']['daily'][] = array('Ferienpass\FerienpassCalendar', 'writeICal');
