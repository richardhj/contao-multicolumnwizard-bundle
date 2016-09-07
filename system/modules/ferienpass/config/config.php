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
    array
    (
        'ferienpass' => array
        (
            'ferienpass_management' => array
            (
                'callback' => 'Ferienpass\BackendModule\Management',
                'tables'   => array(),
                'icon'     => 'system/modules/ferienpass/assets/img/equalizer.png',
            ),
        ),
    )
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
$GLOBALS['FERIENPASS_MOD'] = array
(
    'data_processing' => array
    (),
    'setup'           => array
    (
        'data_processings'  => array
        (
            'tables' => array(\Ferienpass\Model\DataProcessing::getTable()),
            'icon'   => 'folder-open',
        ),
        'documents'         => array
        (
            'tables' => array(\Ferienpass\Model\Document::getTable()),
            'icon'   => 'file-text-o',
        ),
        'attendance_status' => array
        (
            'tables' => array(\Ferienpass\Model\AttendanceStatus::getTable()),
            'icon'   => 'th-list',
        ),
        'ferienpass_config' => array
        (
            'tables' => array('tl_ferienpass_config'),
            'icon'   => 'cogs',
        ),
    ),
);

if ($_GET['do'] == 'ferienpass_management') {
    foreach ($GLOBALS['FERIENPASS_MOD'] as $strGroup => $arrModules) {
        if ($strGroup == 'data_processing') {
            $objProcessings = \Ferienpass\Model\DataProcessing::findAll();

            while (null !== $objProcessings && $objProcessings->next()) {
                $GLOBALS['FERIENPASS_MOD'][$strGroup]['data_processing_'.$objProcessings->id] = array
                (
                    'callback' => 'Ferienpass\BackendModule\DataProcessing',
                    'icon'     => 'file-archive-o',
                );
            }
        }


        foreach ($arrModules as $strModule => $arrConfig) {
            // Enable tables in ferienpass_setup
            if (is_array($arrConfig['tables'])) {
                $GLOBALS['BE_MOD']['ferienpass']['ferienpass_management']['tables'] = array_merge(
                    $GLOBALS['BE_MOD']['ferienpass']['ferienpass_management']['tables'],
                    $arrConfig['tables']
                );
            }
        }
    }
}


/**
 * Ferienpass status
 */
$GLOBALS['FERIENPASS_STATUS'] = array
(
    'confirmed',
    'waiting',
    'error',
);


/**
 * Back end styles
 */
if (TL_MODE == 'BE') {
    $GLOBALS['TL_CSS'][] = 'system/modules/ferienpass/assets/css/backend.min.css';

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
    array(
        'ferienpass' => array
        (
            'offer_al_status_change' => array
            (
                'recipients'            => array
                (
                    'participant_email',
                    'host_email',
                    'admin_email',
                    'member_email',
                ),
                'sms_recipients'        => array
                (
                    'member_mobile',
                    'member_phone',
                    'participant_mobile',
                    'participant_phone',
                ),
                'email_text'            => array
                (
                    'offer_*',
                    'participant_*',
                    'member_*',
                ),
                'email_html'            => array
                (
                    'offer_*',
                    'participant_*',
                    'member_*',
                ),
                'email_sender_name'     => array
                (
                    'admin_email',
                ),
                'email_sender_address'  => array
                (
                    'admin_email'
                ),
                'email_recipient_cc'    => array
                (
                    'admin_email'
                ),
                'email_recipient_bcc'   => array
                (
                    'admin_email'
                ),
                'email_replyTo'         => array
                (
                    'admin_email'
                ),
                'sms_text'              => array
                (
                    'offer_*',
                    'participant_*',
                    'member_*',
                ),
                'sms_recipients_region' => array
                (
                    'participant_country',
                    'member_country',
                ),
            ),
        ),
    )
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
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = array('Ferienpass\Helper\InsertTags', 'replaceInsertTags');
$GLOBALS['TL_HOOKS']['createNewUser'][] = array('Ferienpass\Helper\UserAccount', 'createNewUser');
$GLOBALS['TL_HOOKS']['closeAccount'][] = array('Ferienpass\Helper\UserAccount', 'closeAccount');
$GLOBALS['TL_HOOKS']['getAllEvents'][] = array('Ferienpass\Helper\Events', 'getMetaModelAsEvents');
$GLOBALS['TL_HOOKS']['simpleAjax'][] = array('Ferienpass\Helper\Ajax', 'handleDropboxWebhook');

//$GLOBALS['TL_DCA'][FerienpassConfig::get(FerienpassConfig::PARTICIPANT_MODEL)]['list']['label']['label_callback'] = array('Ferienpass\Helper\Dca', 'test');

/**
 * Cron jobs
 */
//$GLOBALS['TL_CRON']['daily'][] = array('Ferienpass\FerienpassCalendar', 'writeICal');
