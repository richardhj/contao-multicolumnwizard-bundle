<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 * Copyright (c) 2015-2015 Richard Henkenjohann
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard-ferienpass@henkenjohann.me>
 */

namespace Ferienpass\Module\Item\Offer;


use Ferienpass\Helper\Message;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\Participant;
use Ferienpass\Module\Item;
use Haste\Form\Form;
use MetaModels\Attribute\IAttribute;
use MetaModels\FrontendEditingItem;

class AddAttendeeHost extends Item
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_offer_addattendeehost';


	/**
	 * {@inheritdoc}
	 * Include permission check
	 */
	public function generate()
	{
		return parent::generate(true);
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		$objForm = new Form('tl_add_attendee_host', 'POST', function ($objHaste)
		{
			/** @noinspection PhpUndefinedMethodInspection */
			return \Input::post('FORM_SUBMIT') === $objHaste->getFormId();
		});

		/*
		 * Fetch participant model attributes
		 */
		$arrColumnFieldsDca = array();
		$arrMemberGroups = deserialize($this->User->groups);

		$objDcaCombine = $this->objDatabase
			->prepare("SELECT * FROM tl_metamodel_dca_combine WHERE fe_group IN(" . implode(',', $arrMemberGroups) . ") AND pid=?")
			->limit(1)
			->execute(Participant::getInstance()->getMetaModel()->get('id'));

		// Throw exception if no dca combine setting is set
		if (!$objDcaCombine->numRows)
		{
			throw new \RuntimeException(sprintf('No dca combine setting found for MetaModel ID %u and member groups %s found', Participant::getInstance()->getMetaModel()->get('id'), var_export($arrMemberGroups, true)));
		}

		// Get the dca settings
		$objDbDca = $this->objDatabase
			->prepare("SELECT * FROM tl_metamodel_dca WHERE id=?")
			->execute($objDcaCombine->dca_id);

		$objDbDcaSetting = $this->objDatabase
			->prepare("SELECT a.colname,s.* FROM tl_metamodel_attribute a INNER JOIN tl_metamodel_dcasetting s ON a.id=s.attr_id WHERE s.pid=?")
			->execute($objDbDca->id);

		// Fetch all dca settings as associative array
		$arrDcaSettings = array_reduce($objDbDcaSetting->fetchAllAssoc(), function ($result, $item)
		{
			$result[$item['colname']] = $item;

			return $result;
		}, array());

		// Exit if a new item creation is not allowed
		if (!$objDbDca->iscreatable)
		{
			Message::addError($GLOBALS['TL_LANG']['MSC']['tableClosedInfo']);

			$this->Template->message = Message::generate();

			return;
		}

		// Add all published attributes and override the dca settings in the field definition
		/**
		 * @var string     $attribute
		 * @var IAttribute $objAttribute
		 */
		foreach (Participant::getInstance()->getMetaModel()->getAttributes() as $attribute => $objAttribute)
		{
			if (!$arrDcaSettings[$attribute]['published'])
			{
				continue;
			}

			$arrColumnFieldsDca[$attribute] = $objAttribute->getFieldDefinition($arrDcaSettings[$attribute]);
		}

		$objForm->addFormField('attendees', array
		(
			'inputType' => 'multiColumnWizard',
			'eval'      => array
			(
				'mandatory'    => true,
				'columnFields' => $arrColumnFieldsDca
			)
		));

		$objForm->addSubmitFormField('submit', $GLOBALS['TL_LANG']['MSC']['addAttendeeHost']['submit']);

		if ($objForm->validate())
		{
			$arrParticipantsToAdd = $objForm->fetch('attendees');

			// Create a new model for each participant
			foreach ($arrParticipantsToAdd as $participant)
			{
				$objParticipant = new FrontendEditingItem(Participant::getInstance()->getMetaModel(), array());

				// Set each attribute in participant model
				foreach ($participant as $attribute => $value)
				{
					$objParticipant->set($attribute, $value);
				}

				$objParticipant->save();

				// Create an attendance for this participant and offer
				$objAttendance = new Attendance();
				$objAttendance->tstamp = time();
				$objAttendance->offer_id = $this->objItem->get('id');
				$objAttendance->participant_id = $objParticipant->get('id');
				$objAttendance->status = $objAttendance->getStatus()->id;
				$objAttendance->save();

			}

			Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['MSC']['addAttendeeHost']['confirmation'], count($arrParticipantsToAdd)));
			\Controller::reload();
		}

		$this->Template->message = Message::generate();
		$this->Template->form = $objForm->generate();
	}
}
