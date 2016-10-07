<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard-ferienpass@henkenjohann.me>
 */


namespace Ferienpass\Helper;

use Ferienpass\Helper\Config as FerienpassConfig;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\Participant;


/**
 * Class UserAccount
 * @package Ferienpass\Helper
 */
class UserAccount extends \Frontend
{

	/**
	 * Check the postal code on user registration
	 * @category HOOK: createNewUser
	 *
	 * @param integer             $intId
	 * @param array               $arrData
	 * @param \ModuleRegistration $objModule
	 */
	public function createNewUser($intId, $arrData, $objModule)
	{
		$arrAllowedZipCodes = trimsplit(',', FerienpassConfig::get(FerienpassConfig::PARTICIPANT_ALLOWED_ZIP_CODES));

		if (empty($arrAllowedZipCodes))
		{
			return;
		}

		// Check for allowed zip code
		if (!in_array($arrData['postal'], $arrAllowedZipCodes))
		{
			// Add error as message
			// !!! You have to include the message in registration template (member_…)
			Message::addError('Ihre Postleitzahl ist für die Registrierung nicht zulässig. Wenn Sie meinen, dass das ein Fehler ist, kontaktieren Sie uns bitte.'); //@todo lang

			$this->deleteUser($intId);
			\Controller::reload();

			return;
		}
	}


	/**
	 * Delete a member's participants and attendances
	 * @category HOOK: closeAccount
	 *
	 * @param integer             $intUserId
	 * @param string              $strRegClose
	 * @param \ModuleCloseAccount $objModule
	 */
	public function closeAccount($intUserId, $strRegClose, $objModule)
	{
		if ($strRegClose != 'close_delete')
		{
			return;
		}

		// Delete attendances
		$objAttendances = Attendance::findByParent($intUserId);
		$intCountAttendances = (null !== $objAttendances) ? $objAttendances->count() : 0;

		while (null !== $objAttendances && $objAttendances->next())
		{
			$objAttendances->delete();
		}

		// Delete participants
		$objParticipants = Participant::getInstance()->findByParent($intUserId);
		$intCountParticipants = $objParticipants->getCount();

		while ($objParticipants->next())
		{
			Participant::getInstance()->getMetaModel()->delete($objParticipants->getItem());
		}
		
		\System::log(sprintf('%u participants and %u attendances for member ID %u has been deleted',
			$intCountParticipants,
			$intCountAttendances,
			$intUserId
		), __METHOD__, TL_GENERAL);
	}


	/**
	 * Delete a user by id
	 *
	 * @param integer $intId
	 */
	protected function deleteUser($intId)
	{
		@\FrontendUser::getInstance()->logout();
		@define('FE_USER_LOGGED_IN', $this->getLoginStatus('FE_USER_AUTH'));
		/** @noinspection PhpUndefinedMethodInspection */
		@\MemberModel::findByPk($intId)->delete();
	}


	/**
	 * Set fields configured in the ferienpass config as mandatory in the dca
	 * @category onload_callback
	 */
	public function setRequiredFields()
	{
		// It is a front end call without a dc
		if (0 === func_num_args())
		{
			foreach (deserialize(FerienpassConfig::get(FerienpassConfig::PARTICIPANT_REGISTRATION_REQUIRED_FIELDS)) as $field)
			{
				$GLOBALS['TL_DCA']['tl_member']['fields'][$field]['eval']['mandatory'] = true;
			}
		}
	}
}
