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

namespace Richardhj\ContaoFerienpassBundle\BackendModule;

use Contao\BackendUser;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\MemberModel;
use Contao\System;
use ContaoCommunityAlliance\DcGeneral\Contao\View\Contao2BackendView\ContaoBackendViewTemplate;
use Doctrine\DBAL\Connection;
use MetaModels\IFactory;
use MetaModels\Render\Setting\IRenderSettingFactory;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use MetaModels\Render\Template;
use NotificationCenter\Model\Notification;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;


/**
 * Class SendMemberAttendancesOverview
 *
 * @package Richardhj\ContaoFerienpassBundle\BackendModule
 */
class SendMemberAttendancesOverview extends \BackendModule
{

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var IFactory
     */
    private $factory;

    /**
     * @var IRenderSettingFactory
     */
    private $renderSettingFactory;

    /**
     * SendMemberAttendancesOverview constructor.
     *
     * @param DataContainer|null $dataContainer
     *
     * @throws ServiceNotFoundException
     * @throws ServiceCircularReferenceException
     */
    public function __construct(DataContainer $dataContainer = null)
    {
        parent::__construct($dataContainer);

        $this->strTemplate          = 'dcbe_general_edit';
        $this->connection           = System::getContainer()->get('database_connection');
        $this->factory              = System::getContainer()->get('metamodels.factory');
        $this->renderSettingFactory = System::getContainer()->get('metamodels.render_setting_factory');
    }

    /**
     * Generate the module
     *
     * @return string
     */
    public function generate(): string
    {
        if (!BackendUser::getInstance()->isAdmin) {
            return sprintf('<p class="tl_gerror">%s</p>', 'keine Berechtigung');
        }

        return parent::generate();
    }

    /**
     * Generate the module
     */
    protected function compile(): void
    {
        $output     = '';
        $formSubmit = 'send_member_attendances_overview';

        if ($formSubmit === Input::post('FORM_SUBMIT')) {
            $this->sendMessages();
        }

        $output .= '<p>Dieses Tool versendet die Teilnahmestatus aus dem Losverfahren für die Anmeldungen, die noch nicht versandt wurden oder die sich nach dem letzen Versenden geändert haben.</p>';

        $members   = $this->getNotSentAttendancesGroupedByMember();
        $noMembers = (0 === \count($members));

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
            /** @var array $attendanceIds */
            foreach ($members as $memberId => $attendanceIds) {
                $member = MemberModel::findByPk($memberId);
                if (null === $member) {
                    continue;
                }

                $class = (++$m % 2) ? ' class="tl_bg"' : '';
                foreach ($attendanceIds as $a => $attendanceId) {
                    $attendance  = Attendance::findByPk($attendanceId);
                    $participant = $attendance->getParticipant();
                    $offer       = $attendance->getOffer();
                    if (null === $participant || null === $offer) {
                        continue;
                    }

                    $output .= '<tr>';
                    if (0 === $a) {
                        $output .= sprintf(
                            '<td rowspan="%s"%s>%s %s</td>',
                            \count($attendanceIds),
                            $class,
                            $member->firstname,
                            $member->lastname
                        );
                    }
                    $output .= sprintf(
                        '<td%s>%s</td>',
                        $class,
                        $participant->parseAttribute('name')['text']
                    );
                    $output .= sprintf(
                        '<td%s>%s</td>',
                        $class,
                        $offer->parseAttribute('name')['text']
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

        $buttonTemplate   = new ContaoBackendViewTemplate('dc_general_button');
        $buttonAttributes = [
            'type'      => 'submit',
            'name'      => 'start',
            'id'        => 'start',
            'class'     => 'tl_submit',
            'accesskey' => 's',
        ];
        if ($noMembers) {
            $buttonAttributes['disabled'] = 'disabled';
        }
        $buttonTemplate->setData(
            [
                'label'      => 'Benachrichtigungen sofort verschicken',
                'attributes' => $buttonAttributes,
            ]
        );

        $buttons['save'] = $buttonTemplate->parse();

        $submitButtons = ['toggleIcon' => Image::getHtml('navcol.svg')];
        $editButtons   = $buttons;
        if (array_key_exists('save', $editButtons)) {
            $submitButtons['save'] = $editButtons['save'];
            unset($editButtons['save']);
        }

        if (0 < \count($editButtons)) {
            $submitButtons['buttonGroup'] = $editButtons;
        }

        $submitButtonTemplate = new ContaoBackendViewTemplate('dc_general_submit_button');
        $submitButtonTemplate->setData($submitButtons);


        $this->Template->subHeadline = 'Teilnahmeübersicht an Eltern verschicken';
        $this->Template->table       = $formSubmit;
        $this->Template->editButtons = preg_replace('/(\s\s+|\t|\n)/', '', $submitButtonTemplate->parse());
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
    private function getNotSentAttendancesGroupedByMember(): array
    {
        $attendances = [];
        //Todo check for notification id
        $qb2 = $this->connection->createQueryBuilder()
            ->select('notification.attendance')
            ->from('tl_ferienpass_attendance_notification', 'notification')
            ->where('notification.tstamp>attendance.tstamp');

        $statement = $this->connection->createQueryBuilder()
            ->select('member.id AS member', 'attendance.id AS attendance')
            ->from('tl_member', 'member')
            ->innerJoin('member', 'mm_participant', 'participant', 'participant.pmember=member.id')
            ->innerJoin(
                'participant',
                'tl_ferienpass_attendance',
                'attendance',
                'attendance.participant=participant.id'
            )
            ->where($qb2->expr()->notIn('attendance.id', $qb2->getSQL()))
            ->execute();

        while ($rows = $statement->fetch(\PDO::FETCH_OBJ)) {
            $attendances[$rows->member][] = $rows->attendance;
        }

        return $attendances;
    }

    /**
     * Trigger notification for all members
     */
    private function sendMessages(): void
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

        foreach ($this->getNotSentAttendancesGroupedByMember() as $memberId => $attendanceIds) {
            if (empty($attendanceIds)) {
                continue;
            }

            // Convert id vars to models
            $member = MemberModel::findByPk($memberId);
            if (null === $member) {
                continue;
            }

            $attendances = array_map(
                function ($id) {
                    return Attendance::findByPk($id);
                },
                $attendanceIds
            );

            // Send notification
            $sent = $notification->send(
                $this->getNotificationTokens(
                    $member,
                    $attendances
                )
            );

            // Mark attendance notification as sent
            if (\in_array(true, $sent, true)) {
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
    private function getNotificationTokens(MemberModel $member, array $attendances): array
    {
        $tokens      = [];
        $attendances = array_filter(
            $attendances,
            function (Attendance $attendance) {
                return (null !== $attendance->getOffer() && null !== $attendance->getParticipant());
            }
        );

        $metaModelFerienpass  = $this->factory->getMetaModel('mm_ferienpass');
        $metaModelParticipant = $this->factory->getMetaModel('mm_participant');
        if (null === $metaModelFerienpass || null === $metaModelParticipant) {
            return [];
        }

        $data = [
            'attendances'               => $attendances,
            'member'                    => $member,
            'offerRenderSettings'       => $this->renderSettingFactory->createCollection($metaModelFerienpass, 4),
            'participantRenderSettings' => $this->renderSettingFactory->createCollection($metaModelParticipant),
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
