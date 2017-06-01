<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Helper;

use ContaoCommunityAlliance\Contao\Bindings\ContaoEvents;
use ContaoCommunityAlliance\Contao\Bindings\Events\System\LogEvent;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\Participant;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Class UserAccount
 * @package Ferienpass\Helper
 */
class UserAccount extends \Frontend
{

    use GetFerienpassConfigTrait;

    /**
     * Check the postal code on user registration
     * @category HOOK: createNewUser
     *
     * @param integer $id
     * @param array   $data
     *
     * @internal param \ModuleRegistration $module
     */
    public function createNewUser($id, $data)
    {
        $allowedZipCodes = $this->getFerienpassConfig()->getRegistrationAllowedZipCodes();
        if (empty($allowedZipCodes)) {
            return;
        }

        if (!in_array($data['postal'], $allowedZipCodes)) {
            // Add error as message
            // !!! You have to include the message in registration template (member_…)
            Message::addError(
                'Ihre Postleitzahl ist für die Registrierung nicht zulässig. Wenn Sie meinen, dass das ein Fehler ist, kontaktieren Sie uns bitte.'
            ); //@todo lang

            $this->deleteUser($id);
            \Controller::reload();

            return;
        }
    }

    /**
     * Delete a member's participants and attendances
     * @category HOOK: closeAccount
     *
     * @param integer $userId
     * @param string  $regClose
     *
     * @internal param \ModuleCloseAccount $module
     */
    public function closeAccount($userId, $regClose)
    {
        if ('close_delete' !== $regClose) {
            return;
        }

        // Delete attendances
        $attendances = Attendance::findByParent($userId);
        $countAttendances = (null !== $attendances) ? $attendances->count() : 0;

        while (null !== $attendances && $attendances->next()) {
            $attendances->delete();
        }

        // Delete participants
        $participants = Participant::getInstance()->findByParent($userId);
        $countParticipants = $participants->getCount();

        while ($participants->next()) {
            Participant::getInstance()->getMetaModel()->delete($participants->getItem());
        }

        $this->getEventDispatcher()->dispatch(
            ContaoEvents::SYSTEM_LOG,
            new LogEvent(
                sprintf(
                    '%u participants and %u attendances for member ID %u has been deleted',
                    $countParticipants,
                    $countAttendances,
                    $userId
                ),
                __METHOD__,
                TL_GENERAL
            )
        );
    }

    /**
     * Set fields configured in the ferienpass config as mandatory in the dca
     * @category onload_callback
     */
    public function setRequiredFields()
    {
        // It is a front end call without a dc
        if (0 === func_num_args()) {
            foreach ($this->getFerienpassConfig()->getRegistrationRequiredFields() as $field) {
                $GLOBALS['TL_DCA']['tl_member']['fields'][$field]['eval']['mandatory'] = true;
            }
        }
    }

    /**
     * Delete a user by id
     *
     * @param integer $id
     */
    private function deleteUser($id)
    {
        @\FrontendUser::getInstance()->logout();
        @define('FE_USER_LOGGED_IN', $this->getLoginStatus('FE_USER_AUTH'));
        /** @noinspection PhpUndefinedMethodInspection */
        @\MemberModel::findByPk($id)->delete();
    }

    /**
     * @return EventDispatcherInterface
     */
    private function getEventDispatcher(): EventDispatcherInterface {
        global $container;

        return $container['event-dispatcher'];
    }
}
