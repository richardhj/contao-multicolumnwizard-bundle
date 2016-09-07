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
use Ferienpass\Model\Document;
use Ferienpass\Model\Participant;
use Ferienpass\Module\Item;
use Ferienpass\Model\Attendance;
use Ferienpass\Helper\Table;
use MetaModels\Filter\Rules\StaticIdList;
use MetaModels\ItemList;


/**
 * Class OfferApplicationListHost
 * @package Ferienpass\Module
 */
class ApplicationListHost extends Item
{
	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_offer_applicationlisthost';


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
		if (!$this->objItem->get(FerienpassConfig::get(FerienpassConfig::OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE)))
		{
			Message::addError($GLOBALS['TL_LANG']['MSC']['applicationList']['inactive']);
			$this->Template->message = Message::generate();

			return;
		}

		$intMaxParticipants = $this->objItem->get(FerienpassConfig::get(FerienpassConfig::OFFER_ATTRIBUTE_APPLICATIONLIST_MAX));

		$objAttendees = Attendance::findByOffer($this->objItem->get('id'));
		$objView = Participant::getInstance()->getMetaModel()->getView($this->metamodel_child_list_view);
		$arrFields = $objView->getSettingNames();
		$arrRows = array();

		if (null !== $objAttendees)
		{
			// Create table head
			foreach ($arrFields as $field)
			{
				$arrRows[0][] = Participant::getInstance()->getMetaModel()->getAttribute($field)->get('name');
			}

			$this->fetchOwnerAttribute(Participant::getInstance()->getMetaModel());

			// Walk each attendee
			while ($objAttendees->next())
			{
				$objParticipant = Participant::getInstance()->findById($objAttendees->participant_id);
				$arrValues = array();

				// Participant is not existent
				if (null === $objParticipant)
				{
					// Delete attendance too
					$objAttendees->current()->delete(); # this will sync the entire list

					continue;
				}

				foreach ($arrFields as $field)
				{
					$value = $objParticipant->parseAttribute($field, null, $objView)['text'];

					// Inherit parent's data
					if (!strlen($value))
					{
						$value = $objParticipant->get($this->objOwnerAttribute->getColName())[$field];
					}

					$arrValues[] = $value;
				}

				$arrRows[] = $arrValues;
			}
		}

		if (empty($arrRows))
		{
			Message::addWarning($GLOBALS['TL_LANG']['MSC']['noAttendances']);
		}
		else
		{
			$this->useHeader = true;
			$this->max_participants = $intMaxParticipants;

			// Define row class callback
			$rowClassCallback = function ($j, $arrRows, $objModule)
			{
				if ($j == ($objModule->max_participants - 1) && $j != count($arrRows) - 1)
				{
					return 'last_attendee';
				}
				elseif ($j >= $objModule->max_participants)
				{
					return 'waiting_list';
				}

				return '';
			};

			$this->Template->dataTable = Table::getDataArray($arrRows, 'application-list', $this, $rowClassCallback);

			// Add download button
			$this->Template->download = $this->document ? sprintf
			(
				'<a href="%1$s" title="%3$s" class="download_list">%2$s</a>',
				$this->addToUrl('action=download_list'),
				$GLOBALS['TL_LANG']['MSC']['downloadList'][0],
				$GLOBALS['TL_LANG']['MSC']['downloadList'][1]
			) : '';

			if (\Input::get('action') == 'download_list')
			{
				if (($objDocument = Document::findByPk($this->document)) === null)
				{
					Message::addError($GLOBALS['TL_LANG']['MSC']['document']['export_error']);
				}
				else
				{
					$objDocument->outputToBrowser($objAttendees);
				}
			}
		}

		$this->addRenderedMetaModelToTemplate();

		$this->Template->message = Message::generate();
	}


	/**
	 * Add the rendered meta model of this offer to the template
	 */
	protected function addRenderedMetaModelToTemplate()
	{
		$objItemRenderer = new ItemList();

		$objItemRenderer
			->setMetaModel($this->metamodel, $this->metamodel_rendersettings)
			->addFilterRule(new StaticIdList(array($this->objItem->get('id'))));

		$this->Template->metamodel = $objItemRenderer->render($this->metamodel_noparsing, $this);
	}
}
