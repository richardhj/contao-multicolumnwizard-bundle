<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Module\Item\Offer;

use Ferienpass\Helper\Config as FerienpassConfig;
use Ferienpass\Helper\Message;
use Ferienpass\Model\Participant;
use Ferienpass\Module\Item;
use Ferienpass\Model\Attendance;
use Haste\DateTime\DateTime;
use Haste\Form\Form;


/**
 * Class OfferApplicationList
 * @package Ferienpass\Module\SingleOffer
 */
class ApplicationList extends Item
{

	/**
	 * @var string
	 */
	protected $strTemplate = 'mod_offer_applicationlist';


	/**
	 * Generate the module
	 */
	protected function compile()
	{
//		$state = $this->objItem->get(FerienpassConfig::get(FerienpassConfig::OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE)) ? 'active' : 'inactive';
//		$this->Template->al_state = $state;

		// Stop if the procedure is not used
		if (!$this->objItem->get(FerienpassConfig::get(FerienpassConfig::OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE)))
		{
			$this->Template->info = $GLOBALS['TL_LANG']['MSC']['applicationList']['inactive'];

			return;
		}

		// Stop if the offer is in the past
		if (time() >= $this->objItem->get(FerienpassConfig::get(FerienpassConfig::OFFER_ATTRIBUTE_DATE_CHECK_AGE)))
		{
			$this->Template->info = $GLOBALS['TL_LANG']['MSC']['applicationList']['past'];

			return;
		}

//		$this->Template->info = $GLOBALS['TL_LANG']['MSC']['applicationList'][$state];

		$intParticipants = Attendance::countParticipants($this->objItem->get('id'));
		$intMaxParticipants = $this->objItem->get(FerienpassConfig::get(FerienpassConfig::OFFER_ATTRIBUTE_APPLICATIONLIST_MAX));

		$intAvailableParticipants = $intMaxParticipants - $intParticipants;

		if ($intMaxParticipants)
		{
			if ($intAvailableParticipants < -10)
			{
				$this->Template->booking_state_code = 4;
				$this->Template->booking_state_text = 'Es sind keine Plätze mehr verfügbar<br>und die Warteliste ist ebenfalls voll.';
			}
			elseif ($intAvailableParticipants < 1)
			{
				$this->Template->booking_state_code = 3;
				$this->Template->booking_state_text = 'Es sind keine freien Plätze mehr verfügbar,<br>aber Sie können sich auf die Warteliste eintragen.';
			}
			elseif ($intAvailableParticipants < 4)
			{
				$this->Template->booking_state_code = 2;
				$this->Template->booking_state_text = 'Es sind nur noch wenige Plätze für dieses Angebot verfügbar.<br>Sie können sich jetzt für das Angebot anmelden.';
			}
			else
			{
				$this->Template->booking_state_code = 1;
				$this->Template->booking_state_text = 'Es sind noch Plätze für dieses Angebot verfügbar.<br>Sie können sich jetzt für das Angebot anmelden.';
			}
		}
		else
		{
			$this->Template->booking_state_code = 0;
			$this->Template->booking_state_text = 'Das Angebot hat keine Teilnehmer-Beschränkung.<br>Sie können sich jetzt für das Angebot anmelden.';
		}


		if (FE_USER_LOGGED_IN && $this->User->id)
		{
			$objParticipants = Participant::getInstance()->findByParent($this->User->id);

			if (0 === $objParticipants->getCount())
			{
				Message::addWarning($GLOBALS['TL_LANG']['MSC']['noParticipants']);
			}

			// Build options
			$arrOptions = array();
			$arrParticipantIds = Participant::getInstance()->byParentAndOfferFilter($this->User->id, $this->objItem->get('id'))->getMatchingIds();
			$arrParticipantIdsAllowed = array();
			$intMaxApplicationsPerDay = FerienpassConfig::get(FerienpassConfig::PARTICIPANT_MAX_APPLICATIONS_PER_DAY);

			while ($objParticipants->next())
			{
				$objDateOfBirth = new DateTime(
					'@' . $objParticipants
						->getItem()
						->get(FerienpassConfig::get(FerienpassConfig::PARTICIPANT_ATTRIBUTE_DATEOFBIRTH))
				);
				$objDateOffer = new DateTime(
					'@' . $this->objItem
						->get(FerienpassConfig::get(FerienpassConfig::OFFER_ATTRIBUTE_DATE_CHECK_AGE))
				);

				$intAge = $objDateOfBirth
					->getAge($objDateOffer); # Use offer's date for diff check

				$blnLimitReached = ($intMaxApplicationsPerDay && Attendance::countByParticipantAndDay($objParticipants->getItem()->get('id')) >= $intMaxApplicationsPerDay) ? true : false;
				$blnAttend = (in_array($objParticipants->getItem()->get('id'), $arrParticipantIds));
				$blnAgeAllowed = in_array($this->objItem->get('id'), $this->objItem->getAttribute(FerienpassConfig::get(FerienpassConfig::OFFER_ATTRIBUTE_AGE))->searchFor($intAge));

				// Check if a participant is allowed for this offer and set the corresponding language key
				if ($blnAttend)
				{
					$languageKey = 'already_attending';
				}
				elseif (!$blnAgeAllowed)
				{
					$languageKey = 'age_not_allowed';
				}
				elseif ($blnLimitReached)
				{
					$languageKey = 'limit_reached';
				}
				else
				{
					$languageKey = 'ok';
					$arrParticipantIdsAllowed[] = $objParticipants->getItem()->get('id');
				}

				$arrOptions[] = array
				(
					'value'    => $objParticipants->getItem()->get('id'),
					'label'    => sprintf(
						$GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['option']['label'][$languageKey],
						$objParticipants->getItem()->parseAttribute(FerienpassConfig::get(FerienpassConfig::PARTICIPANT_ATTRIBUTE_NAME))['text'] # = parsed participant name
					),
					'disabled' => ($languageKey != 'ok')
				);
			}

			// Create form instance
			$objForm = new Form('al' . $this->id, 'POST', function ($objHaste)
			{
				/** @noinspection PhpUndefinedMethodInspection */
				return \Input::post('FORM_SUBMIT') === $objHaste->getFormId();
			});

			$objForm->addFormField('participant', array
			(
				'label'     => $GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['label'],
				'inputType' => 'select_disabled_options',
				'eval'      => array
				(
					'options'     => $arrOptions,
					'addSubmit'   => true,
					'slabel'      => $GLOBALS['TL_LANG']['MSC']['applicationList']['participant']['slabel'],
					'multiple'    => true,
					'mandatory'   => true,
					'chosen'      => true,
					'placeholder' => 'Hier klicken und Teilnehmer auswählen' //@todo lang
				)
			));


			// Validate the form
			if ($objForm->validate())
			{
				// Process new applications
				foreach ((array)$objForm->fetch('participant') as $participant)
				{
					// Check if participant id allowed here and attendance not existent yet
					if (in_array($participant, $arrParticipantIdsAllowed) && Attendance::isNotExistent($participant, $this->objItem->get('id')))
					{
						// Set new attendance
						$objAttendance = new Attendance();
						$objAttendance->tstamp = time();
						$objAttendance->offer_id = $this->objItem->get('id');
						$objAttendance->participant_id = $participant;

						// Fetch status
						$objStatus = $objAttendance->getStatus();
						$objAttendance->status = $objStatus->id;

						// Save attendance
						$objAttendance->save();

						$strParticipantName = Participant::getInstance()->findById($participant)->parseAttribute(FerienpassConfig::get(FerienpassConfig::PARTICIPANT_ATTRIBUTE_NAME))['text'];

						// Add message corresponding to attendance's status
						switch ($objStatus->type)
						{
							case 'confirmed':
								Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['MSC']['applicationList']['message'][$objStatus->type], $strParticipantName));
								break;

							case 'waiting':
								Message::addWarning(sprintf($GLOBALS['TL_LANG']['MSC']['applicationList']['message'][$objStatus->type], $strParticipantName));
								break;

							case 'error':
								Message::addError(sprintf($GLOBALS['TL_LANG']['MSC']['applicationList']['message'][$objStatus->type], $strParticipantName));
								break;
						}
					}

					// Attendance already exists
					else
					{
						Message::addError($GLOBALS['TL_LANG']['MSC']['applicationList']['error']);

						return;
					}
				}

				// Reload page to show confirmation message
				\Controller::reload();
			}

			// Get the form as string
			$this->Template->form = $objForm->generate();
		}

		$this->Template->message = Message::generate();
	}
}
