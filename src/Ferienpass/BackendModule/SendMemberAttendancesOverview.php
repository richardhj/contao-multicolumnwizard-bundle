<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\BackendModule;

use Contao\MemberModel;
use Ferienpass\Model\Attendance;
use MetaModels\IMetaModelsServiceContainer;
use MetaModels\Render\Template;
use NotificationCenter\Model\Notification;


/**
 * Class SendMemberAttendancesOverview
 *
 * @package Ferienpass\BackendModule
 */
class SendMemberAttendancesOverview extends \BackendModule
{

    protected $strTemplate = 'dcbe_general_edit';


    /**
     * Generate the module
     *
     * @return string
     */
    public function generate()
    {
        if (!\BackendUser::getInstance()->isAdmin) {
            return sprintf('<p class="tl_gerror">%s</p>', 'keine Berechtigung');
        }

        return parent::generate();
    }


    /**
     * Generate the module
     */
    protected function compile()
    {
        $output     = '';
        $formSubmit = 'send_member_attendances_overview';

        if ($formSubmit === \Input::post('FORM_SUBMIT')) {
            $this->sendMessages();
        }

        $output .= '<p>Dieses Tool versendet die Teilnahmestatus aus dem Losverfahren für die Anmeldungen, die noch nicht versandt wurden oder die sich nach dem letzen Versenden geändert haben.</p>';

        $members   = self::getNotSentAttendancesGroupedByMember();
        $noMembers = (0 === count($members));

        if ($noMembers) {
            $output .= '<p class="tl_info">Es gibt keine Mitglieder mit noch nicht versandten Teilnehmerübersichten.</p>';
        } else {
            $output .= <<<'HTML'
<table class="tl_show">
    <tbody>
    <tr>
        <th>Mitglied</th>
        <th>Teilnehmer</th>
        <th>Angebot</th>
        <th>Status</th>
    </tr>
HTML;

            $m = 0;
            foreach ($members as $memberId => $attendanceIds) {
                $member = MemberModel::findByPk($memberId);
                $class  = (++$m % 2) ? ' class="tl_bg"' : '';
                foreach ($attendanceIds as $a => $attendanceId) {
                    $attendance = Attendance::findByPk($attendanceId);

                    $output .= '<tr>';
                    if (0 === $a) {
                        $output .= sprintf(
                            '<td rowspan="%s"%s>%s %s</td>',
                            count($attendanceIds),
                            $class,
                            $member->firstname,
                            $member->lastname
                        );
                    }
                    $output .= sprintf(
                        '<td%s>%s</td>',
                        $class,
                        $attendance->getParticipant()->parseAttribute('name')['text']
                    );
                    $output .= sprintf(
                        '<td%s>%s</td>',
                        $class,
                        $attendance->getOffer()->parseAttribute('name')['text']
                    );
                    $output .= sprintf('<td%s>%s</td>', $class, $attendance->getStatus()->title);
                    $output .= '</tr>';
                }
            }

            $output .= <<<'HTML'
    </tbody>
</table>
HTML;
        }

        $buttons[] = sprintf(
            '<input type="submit" name="start" id="start" class="tl_submit" accesskey="s" value="%s"%s />',
            'Benachrichtigungen sofort verschicken',
            ($noMembers) ? ' disabled="disabled"' : ''
        );

        $this->Template->subHeadline = 'Teilnahmeübersicht an Eltern verschicken';
        $this->Template->table       = $formSubmit;
        $this->Template->editButtons = $buttons;
        $this->Template->fieldsets   = [
            [
                'class'   => 'tl_box',
                'palette' => $output,
            ],
        ];
    }


    /**
     * Fetch all attendances that were not sent already grouped by member
     *
     * @return array
     */
    private static function getNotSentAttendancesGroupedByMember()
    {
        $attendances = [];
        $query       = \Database::getInstance()->query(
            <<<'SQL'
SELECT member.id AS member, attendance.id AS attendance
FROM tl_member member
INNER JOIN mm_participant participant ON participant.pmember=member.id
INNER JOIN tl_ferienpass_attendance attendance ON attendance.participant=participant.id
WHERE attendance.id NOT IN (SELECT notification.attendance FROM tl_ferienpass_attendance_notification notification WHERE notification.tstamp>attendance.tstamp)
SQL
        );

        while ($query->next()) {
            $attendances[$query->member][] = $query->attendance;
        }

        return $attendances;
    }


    /**
     * Trigger notification for all members
     */
    private function sendMessages()
    {
        //@todo
        $notificationId = 6;
        /** @var Notification $notification */
        /** @noinspection PhpUndefinedMethodInspection */
        $notification = Notification::findByPk($notificationId);

        if (null === $notification) {
            return;
        }

        $successful = 0;
        $failed     = 0;

        foreach (self::getNotSentAttendancesGroupedByMember() as $memberId => $attendanceIds) {
            if (empty($attendanceIds)) {
                continue;
            }

            // Convert id vars to models
            $member      = MemberModel::findByPk($memberId);
            $attendances = array_map(
                function ($id) {
                    return Attendance::findByPk($id);
                },
                $attendanceIds
            );

            // Send notification
            $sent = $notification->send(
                self::getNotificationTokens(
                    $member,
                    $attendances
                )
            );

            // Mark attendance notification as sent
            if (in_array(true, $sent)) {
                $time   = time();
                $values = array_map(
                    function ($attendance) use ($time, $notificationId) {
                        return sprintf('(%s, %s, %s)', $time, $attendance, $notificationId);
                    },
                    $attendanceIds
                );

                \Database::getInstance()->query(
                    'INSERT INTO tl_ferienpass_attendance_notification (tstamp, attendance, notification)' .
                    ' VALUES ' . implode(', ', $values) .
                    ' ON DUPLICATE KEY UPDATE tstamp=' . $time
                );

                $successful++;
            } else {
                $failed++;
            }
        }

        // Add confirmation messages
        if ($successful) {
            // todo lang
            \Message::addConfirmation(sprintf('%s Eltern wurden erfolgreich benachrichtigt.', $successful));
        }
        if ($failed) {
            \Message::addError(
                sprintf('%s Eltern konnten nicht benachrichtigt werden! Details im System-Log.', $failed)
            );
        }
    }


    /**
     * Build tokens array for notification
     *
     * @param MemberModel|\Model $member
     * @param Attendance[]       $attendances
     *
     * @return array
     */
    private static function getNotificationTokens(MemberModel $member, array $attendances)
    {
        global $container;

        /** @var IMetaModelsServiceContainer $serviceContainer */
        $serviceContainer = $container['metamodels-service-container'];

        $tokens = [];
        //@todo
        $data   = [
            'attendances'               => $attendances,
            'member'                    => $member,
            'offerRenderSettings'       => $serviceContainer->getFactory()->getMetaModel('mm_ferienpass')->getView(4),
            'participantRenderSettings' => $serviceContainer->getFactory()->getMetaModel('mm_participant')->getView(0)
        ];

        // Add all member fields
        foreach ($member->row() as $k => $v) {
            $tokens['member_' . $k] = $v;
        }

        // Parse applications and add them to the tokens
        $tokens['applications_html'] = Template::render('applications_member_overview', 'html5', $data);
        $tokens['applications_text'] = Template::render('applications_member_overview', 'text', $data);

        // Add the admin's email
        $tokens['admin_email'] = $GLOBALS['TL_ADMIN_EMAIL'];

        return $tokens;
    }
}
