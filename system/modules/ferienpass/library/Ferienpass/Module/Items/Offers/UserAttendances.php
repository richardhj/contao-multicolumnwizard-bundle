<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Module\Items\Offers;

use Contao\Controller;
use Contao\Input;
use Contao\RequestToken;
use Ferienpass\Helper\Config as FerienpassConfig;
use Ferienpass\Helper\Message;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\AttendanceStatus;
use Ferienpass\Model\Participant;
use Ferienpass\Module\Items;
use Ferienpass\Helper\Table;


class UserAttendances extends Items
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_user_attendances';


	/**
	 * {@inheritdoc}
	 */
	public function generate()
	{
		// Load language file
		Controller::loadLanguageFile('exception');

		return parent::generate();
	}


	/**
	 * {@inheritdoc}
	 */
	protected function compile()
	{
		/*
		 * Delete attendance
		 */
		if ('delete' === substr(Input::get('action'), 0, 6))
		{
			list(, $id, $rt) = trimsplit('::', Input::get('action'));
			$objAttendanceDelete = Attendance::findByPk($id);

			// Validate request token
			if (!RequestToken::validate($rt))
			{
				Message::addError($GLOBALS['TL_LANG']['XPT']['tokenRetry']);
			}
			// Check for existence
			elseif (null === $objAttendanceDelete)
			{
				Message::addError($GLOBALS['TL_LANG']['XPT']['attendanceDeleteNotFound']);
			}
			// Check for permission
			elseif (!Participant::getInstance()->isProperChild($objAttendanceDelete->participant_id, $this->User->id))
			{
				Message::addError($GLOBALS['TL_LANG']['XPT']['attendanceDeleteMissingPermission']);
				\System::log(sprintf('User "%s" does not have the permission to delete attendance ID %u', $this->User->username, $objAttendanceDelete->id), __METHOD__, TL_ERROR);
			}
			// Check for offer's date
			elseif ($this->objMetaModel->findById($objAttendanceDelete->offer_id)->get(FerienpassConfig::get(FerienpassConfig::OFFER_ATTRIBUTE_DATE_CHECK_AGE)) <= time())
			{
				Message::addError($GLOBALS['TL_LANG']['XPT']['attendanceDeleteOfferInPast']);
			}
			// Delete
			else
			{
				$objAttendanceDelete->delete();

				Message::addConfirmation($GLOBALS['TL_LANG']['MSC']['attendanceDeletedConfirmation']);
				Controller::redirect($this->addToUrl('action='));
			}
		}

		/*
		 * Create table
		 */
		$objAttendances = Attendance::findByParent($this->User->id);

		$arrRows = array();
		$arrFields = ['offer.name', 'participant.name', 'offer.date_combined', 'state', 'details', 'recall'];

		if (null !== $objAttendances)
		{
			// Create table head
			foreach ($arrFields as $field)
			{
				$f = trimsplit('.', $field);
				$key = (strpos($field, '.') !== false) ? $f[1] : $field;

				switch ($f[0])
				{
					case 'offer':
						$arrRows[0][] = $this->objMetaModel->getAttribute($key)->getName();
						break;

					case 'participant':
						$arrRows[0][] = Participant::getInstance()->getMetaModel()->getAttribute($key)->getName();
						break;

					case 'details':
					case 'recall':
						$arrRows[0][] = '&nbsp;';
						break;

					default:
						$arrRows[0][] = $GLOBALS['TL_LANG']['MSC'][$key];
						break;
				}
			}

			// Walk each attendee
			while ($objAttendances->next())
			{
				$arrValues = array();

				foreach ($arrFields as $field)
				{
					$f = trimsplit('.', $field);
					/** @var \MetaModels\Item $objItem */
					$objItem = $this->objMetaModel->findById($objAttendances->offer_id);

					switch ($f[0])
					{
						case 'offer':
							$value = $objItem->parseAttribute($f[1])['text'];
							break;

						case 'participant':
							$value = Participant::getInstance()->findById($objAttendances->participant_id)->get($f[1]);
							break;

						case 'state':
							/** @var AttendanceStatus $objStatus */
							$objStatus = AttendanceStatus::findByPk($objAttendances->status);
							$value = sprintf('<span class="state %s">%s</span>', $objStatus->cssClass, $objStatus->title ?: $objStatus->name);
							break;

						case 'details':
							$url = $objItem->buildJumpToLink($this->objMetaModel->getView(4))['url'];//@todo make configurable
							$attribute = ($this->openLightbox) ? ' data-lightbox="' : '';
							
							$value = sprintf('<a href="%s" class="%s"%s>%s</a>', $url, $f[0], $attribute, $GLOBALS['TL_LANG']['MSC'][$f[0]]);
							break;
						
						case 'recall':
							if ($objItem->get(FerienpassConfig::get(FerienpassConfig::OFFER_ATTRIBUTE_DATE_CHECK_AGE)) >= time()) {
								$url = $this->addToUrl('action=delete::'.$objAttendances->id.'::'.REQUEST_TOKEN);
								$attribute = ' onclick="return confirm(\''.htmlspecialchars(
										sprintf(
											$GLOBALS['TL_LANG']['MSC']['attendanceConfirmDeleteLink'],
											$objItem->parseAttribute(
												FerienpassConfig::get(FerienpassConfig::OFFER_ATTRIBUTE_NAME)
											)['text'],
											Participant::getInstance()->findById(
												$objAttendances->participant_id
											)->parseAttribute(
												FerienpassConfig::get(FerienpassConfig::PARTICIPANT_ATTRIBUTE_NAME)
											)['text']
										)
									)
									.'\')"';

								$value = sprintf(
									'<a href="%s" class="%s"%s>%s</a>',
									$url,
									$f[0],
									$attribute,
									$GLOBALS['TL_LANG']['MSC'][$f[0]]
								);
							}
							else {
								$value = '';
							}
							break;

						default:
							$value = $objAttendances->$f[1];
							break;
					}

					$arrValues[] = $value;
				}

				$arrRows[] = $arrValues;
			}

			if (count($arrRows) <= 1)
			{
				Message::addInformation($GLOBALS['TL_LANG']['MSC']['noAttendances']);
			}
			else
			{
				$this->useHeader = true;
				$this->Template->dataTable = Table::getDataArray($arrRows, 'user-attendances', $this);
			}
		}
		else
		{
			Message::addWarning($GLOBALS['TL_LANG']['MSC']['noParticipants']);
		}

		$this->Template->message = Message::generate();
	}
}
