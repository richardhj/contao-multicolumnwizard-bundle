<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Module\Items;

use Ferienpass\Helper\Message;
use Ferienpass\Helper\Config as FerienpassConfig;
use Ferienpass\Model\Attendance;
use Ferienpass\Model\DataProcessing;
use Ferienpass\Module\Items;
use Haste\Form\Form;
use MetaModels\IItem;
use MetaModels\IItems;
use MetaModels\FrontendEditingItem as Item;


/**
 * Class OfferEditing
 *
 * @property \FrontendTemplate Template
 * @property bool              isNewItem
 *
 * @package Ferienpass\Module
 */
class Editing extends Items
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'offer_editing_default';


	/**
	 * Provide item for class
	 */
	public function generate()
	{
		// Try to load the the item by its auto_item
		if (!$this->fetchItem())
		{
			// Generate 404 if item not found
			if ($this->strAutoItem)
			{
				$this->exitWith404();
			}

			// Otherwise create a new item for the referenced owner
			$this->fetchOwnerAttribute();

			$this->objItem = new Item
			(
				$this->objMetaModel,
				array
				(
					$this->objOwnerAttribute->getColName() => $this->User->getData()
				)
			);

			// Prepare variant creation
			if (($intVarGroup = (int)\Input::get('vargroup')))
			{
				$objParentItem = $this->objMetaModel->findById($intVarGroup);

				// Exit if permissions for provided var group are insufficient
				if ($objParentItem->get($this->objOwnerAttribute->getColName())['id'] != $this->User->id)
				{
					$this->exitWith403();
				}

				// Set a copy as current item
				$this->objItem = $objParentItem->varCopy();

				// Remove alias to trigger the auto generation
				$this->objItem->set($this->aliasColName, null);
			}

			$this->isNewItem = true;
		}
		else
		{
			// Check permission for editing an existent item
			$this->checkPermission();
		}

		return parent::generate();
	}


	/**
	 * Generate the module
	 * @throws \Exception
	 */
	protected function compile()
	{
		$arrMemberGroups = deserialize($this->User->groups);

		$objDcaCombine = $this->objDatabase
			->prepare("SELECT * FROM tl_metamodel_dca_combine WHERE fe_group IN(" . implode(',', $arrMemberGroups) . ") AND pid=?")
			->limit(1)
			->execute($this->objMetaModel->get('id'));

		// Throw exception if no dca combine setting is set
		if (!$objDcaCombine->numRows)
		{
			throw new \RuntimeException(sprintf('No dca combine setting found for MetaModel ID %u and member groups %s found', $this->objMetaModel->get('id'), var_export($arrMemberGroups, true)));
		}

		/*
		 * Get dca and attribute settings
		 */
		$objDbDca = $this->objDatabase
			->prepare("SELECT * FROM tl_metamodel_dca WHERE id=?")
			->execute($objDcaCombine->dca_id);

		$objDbAttributes = $this->objDatabase
			->prepare("SELECT a.*,s.*,c.type as condition_type, c.attr_id as condition_attr_id, (SELECT colname FROM tl_metamodel_attribute WHERE id=c.attr_id) as condition_attr_name, c.value as condition_value FROM tl_metamodel_attribute a INNER JOIN tl_metamodel_dcasetting s ON a.id=s.attr_id LEFT JOIN tl_metamodel_dcasetting_condition c ON c.settingId=s.id AND c.enabled=1 WHERE s.pid=? AND s.published=1 ORDER BY s.sorting ASC")
			->execute($objDbDca->id);

		// Exit if a new item creation is not allowed
		if ($this->isNewItem && (!$objDbDca->iscreatable || !$objDbDca->iseditable))
		{
			Message::addError($GLOBALS['TL_LANG']['MSC']['tableClosedInfo']);

			$this->Template->message = Message::generate();

			return;
		}

		/*
		 * Vars
		 */
		$isLocked = !$objDbDca->iseditable;
		$blnModified = false;
		$blnEditVariants = false; # Only true if item is variant base

		// Inform about not editable offer
		if ($isLocked)
		{
			Message::addInformation($GLOBALS['TL_LANG']['MSC']['tableClosedInfo']);
		}

		/** @type \Model $objPage */
		global $objPage;

		/*
		 * Build the form
		 */
		$objForm = new Form($this->objMetaModel->getTableName() . '_' . $this->id, 'POST', function ($objHaste)
		{
			/** @noinspection PhpUndefinedMethodInspection */
			return \Input::post('FORM_SUBMIT') === $objHaste->getFormId();
		});

		// Create variant changer for variant base
		if ($this->enableVariants && !$this->objItem->isVariant()) # do not use isVariantBase() because it returns false if the item is new
		{
			$this->addSubmitOnChangeForInput('#ctrl_variants .radio');

			if (\Input::post('variants') == 'y')
			{
				$blnEditVariants = true;
			}
			elseif (\Input::post('variants') == 'n')
			{
				$blnEditVariants = false;
			}
			else
			{
				$blnEditVariants = ($this->objMetaModel->findVariants($this->objItem->get('id'), null)->getCount() != 0);
			}

			$objForm->addFormField('variants', array
			(
				'inputType' => 'radio',
				'default'   => 'n',
				'value'     => $blnEditVariants ? 'y' : 'n',
				'options'   => array('n', 'y'),
				'reference' => $GLOBALS['TL_LANG']['MSC']['enableVariantsOptions'],
			));
		}

		// Walk every attribute
		while ($objDbAttributes->next())
		{
			// Skip non-variant-attributes if item is variant AND skip variant-attributes if item is variant base
			if (($this->objItem->isVariant() && !$objDbAttributes->isvariant)
				|| ($blnEditVariants && $objDbAttributes->isvariant)
			)
			{
				continue;
			}

			// Process dca condition check
			switch ($objDbAttributes->condition_type)
			{
				case 'conditionpropertyvalueis':
					if ($this->objItem->get($objDbAttributes->condition_attr_name) != $objDbAttributes->condition_value)
					{
						continue 2;
					}
			}

			$field = $objDbAttributes->colname;

			$objAttribute = $this->objMetaModel->getAttribute($field);

			$arrData = array_merge
			(
				$objAttribute->getFieldDefinition
				(
					array
					(
						'mandatory' => $objDbAttributes->mandatory,
						'readonly'  => $isLocked
					)
				),
				array
				(
					'value' => $this->objMetaModel
						->getAttribute($field)
						->valueToWidget($this->objItem->get($field))
				)
			);

			// Modify arrData by attribute's type
			switch ($objAttribute->get('type'))
				/** @noinspection PhpMissingBreakStatementInspection */
			{
				// Add date picker for timestamp attributes
				case 'timestamp':

					// @todo this should be done with a setting in the dca and not hardcoded
					if ($this->objMetaModel->get('tableName') == FerienpassConfig::get(FerienpassConfig::PARTICIPANT_MODEL))
					{
						continue;
					}

					$useTimePicker = (in_array($objAttribute->get('timetype'), ['datim', 'time'])) ? 'true' : 'false';
					$GLOBALS['TL_JQUERY'][] = <<<HTML
<script>
	(function ($) {
		$(document).ready(function () {
			$.datetimepicker.setLocale('{$objPage->rootLanguage}');

			$('#ctrl_{$objAttribute->get('colname')}').datetimepicker( {
				timepicker: {$useTimePicker},
 				format: '{$GLOBALS['TL_CONFIG'][$objAttribute->get('timetype') . 'Format']}'
 			});
		});
	})(jQuery);
</script>
HTML;
				// Call hooks
				default:
					if (isset($GLOBALS['FP_HOOKS']['alterEditingFormField']) && is_array($GLOBALS['FP_HOOKS']['alterEditingFormField']))
					{
						foreach ($GLOBALS['FP_HOOKS']['alterEditingFormField'] as $varCallback)
						{
							$arrData = (is_callable($varCallback)) ? call_user_func($varCallback, $arrData, $objAttribute, $objForm) : $arrData;
						}
					}
			}


			if ($objAttribute->get('colname') == 'infotable' && !$objForm->isSubmitted() && empty($arrData['value']))
			{
				$arrData['value'] = array
				(
					array
					(
						'col_0' => 'Ort',
						'col_1' => ''
					),
					array
					(
						'col_0' => 'Kosten',
						'col_1' => ''
					),
					array
					(
						'col_0' => 'Anmeldung',
						'col_1' => ''
					),
					array
					(
						'col_0' => 'max. Teilnehmer',
						'col_1' => ''
					),
					array
					(
						'col_0' => 'Mitbringen',
						'col_1' => ''
					),

					array
					(
						'col_0' => 'Zu beachten',
						'col_1' => ''
					),
					array
					(
						'col_0' => 'Veranstalter',
						'col_1' => ''
					),
					array
					(
						'col_0' => 'Verantwortlich',
						'col_1' => ''
					)
				);
			}//@todo refactor: move


			// Handle submitOnChange
			if ($objDbAttributes->submitOnChange)
			{
				$this->addSubmitOnChangeForInput('#ctrl_' . $field);
			}

			$objForm->addFormField($field, $arrData);
		}

		//@todo refactor: move
		if ($this->objMetaModel->getTableName() == FerienpassConfig::get(FerienpassConfig::PARTICIPANT_MODEL))
		{
			// Add validator for participant's dateOfBirth
			// It must not be changed afterwards
			$objForm->addValidator(FerienpassConfig::get(FerienpassConfig::PARTICIPANT_ATTRIBUTE_DATEOFBIRTH), function ($varValue, $objWidget, $objForm)
			{
				if ($varValue != $this->objItem->get($objWidget->name))
				{
					if (Attendance::countByParticipant($this->objItem->get('id')))
					{
						throw new \Exception($GLOBALS['TL_LANG']['ERR']['changedDateOfBirthAfterwards']);
					}
				}
			});

			// Add validator for participant's agreement for photos
			// It must not be revoked afterwards
			$objForm->addValidator(FerienpassConfig::get(FerienpassConfig::PARTICIPANT_ATTRIBUTE_AGREEMENT_PHOTOS), function ($varValue, $objWidget, $objForm)
			{
				// Allow to grant but not to revoke
				if ($varValue != $this->objItem->get($objWidget->name) && !$varValue)
				{
					if (Attendance::countByParticipant($this->objItem->get('id')))
					{
						throw new \Exception($GLOBALS['TL_LANG']['ERR']['changedAgreementPhotosAfterwards']);
					}
				}
			});
		}

		$objForm->addSubmitFormField('save', $GLOBALS['TL_LANG']['MSC']['saveData']);

		/*
		 * Validate the form data
		 */
		if ($objForm->validate())
		{
			foreach ($objForm->fetchAll() as $strName => $varValue)
			{
				/*
				 * Set the new field value
				 */
				if ($this->objMetaModel->hasAttribute($strName))
				{
					$blnModified = true;
					$this->objItem->set
					(
						$strName,
						$this->objMetaModel
							->getAttribute($strName)
							->widgetToValue($varValue, $this->objItem->get('id'))
					);
				}
			}

			/*
			 * Save the MetaModel item
			 */
			if ($blnModified && !$isLocked)
			{
				// Save item
				$this->objItem->save();

//				// Trigger data processing synchronisation
//				/** @type \Model\Collection|DataProcessing $objProcesings */
//				$objProcesings = DataProcessing::findBy(array('sync=1', 'filesystem<>?'), array('sendToBrowser'));
//
//				while (null !== $objProcesings && $objProcesings->next())
//				{
//					$objProcesings->current()->run(array($this->objItem->get('id')));
//				}

				// Redirect to item editing page if item is new
				if ($this->isNewItem)
				{
					if ($this->objItem->isVariant())
					{
						// Save variant base to persist non variant attributes
						$this->objMetaModel->saveItem
						(
							$this->objMetaModel->findById($this->objItem->get('vargroup'))
						);

						// Output confirmation message
						Message::addConfirmation(sprintf('Diese Variante zum Angebot "%s" wurde erfolgreich erstellt.', $this->objItem->get('name'))); //@todo lang
					}

					\Controller::redirect($this->addToUrl(
						sprintf
						(
							'items=%s&vargroup=',
							$this->objItem->get($this->aliasColName)
						)
					));
				}
			}

			//@todo enhance || being a variant doesn't mean it is opened in a lightbox || we don't want JS here
			// auto-close colorbox
			if ($this->objMetaModel->get('id') == 2 || $this->objItem->isVariant())
			{
				$GLOBALS['TL_JQUERY'][] = <<<HTML
<script>
    parent.jQuery.colorbox.close();
</script>
HTML;
			}
			// Check whether there is a jumpTo page
			elseif (($objJumpTo = $this->objModel->getRelated('jumpTo')) !== null)
			{
				$this->jumpToOrReload($objJumpTo->row());
			}
		}

		if ($blnEditVariants && $this->objItem->isVariantBase())
		{
			$this->Template->variants =
				$this->newVariantLink()
				. $this->variantsCount($this->objMetaModel->findVariants($this->objItem->get('id'), null));
		}

		$this->Template->form = $objForm->generate();
		$this->Template->message = Message::generate();
	}


	protected function newVariantLink()
	{
		$objTemplate = new \FrontendTemplate('ce_hyperlink');
		$objTemplate->class = 'create_new_variant';

		//@todo IF lightbox
		$objTemplate->attribute = ' data-lightbox="" data-lightbox-iframe="" data-lightbox-reload=""';

		$objTemplate->href = sprintf
		(
			'%s?vargroup=%u',
			rtrim(str_replace($this->strAutoItem, '', \Environment::get('request')), '/') . '-variante', //@todo be configurable
			$this->objItem->get('id')
		);

		$objTemplate->link = 'Variante erstellen';
		$objTemplate->linkTitle = specialchars(sprintf('Eine neue Variante zum Element "%s" erstellen', $this->objItem->get('name'))); //@todo lang

		return $objTemplate->parse();
	}


	/**
	 * @param IItem[]|IItems $objVariants
	 *
	 * @return string
	 */
	protected function variantsMenu($objVariants)
	{
		$objTemplate = new \FrontendTemplate('nav_default');
		$objTemplate->level = 'variants';

		$arrItems = array();

		while ($objVariants->next())
		{
			$arrItems[] = array
			(
				'class'  => 'edit-variant',
				'href'   => str_replace($this->objItem->get($this->aliasColName), $objVariants->getItem()->get($this->aliasColName), \Environment::get('request')),
				'title'  => sprintf(specialchars('Die Variante "%s" bearbeiten'), $objVariants->getItem()->get('name')), //@todo lang
				'target' => ' data-lightbox=""',
				'link'   => $objVariants->getItem()->get('name')
			);
		}

		$objTemplate->items = $arrItems;

		return $objTemplate->parse();
	}


	/**
	 * @param IItem[]|IItems $objVariants
	 *
	 * @return string
	 */
	protected function variantsCount($objVariants)
	{
		return sprintf('<p class="count_variants">Es existieren aktuell %u Varianten zu diesem Angebot.</p>', $objVariants->getCount()); //@todo lang
	}


	/**
	 * Add a submitOnChange handler for a specific input
	 *
	 * @param string $strInputId The jQuery selector
	 */
	protected function addSubmitOnChangeForInput($strInputId)
	{
		if (strlen($GLOBALS['TL_JQUERY']['mmEditingSubmitOnChange']))
		{
			$GLOBALS['TL_JQUERY']['mmEditingSubmitOnChange'] = preg_replace_callback('/(\$\(\')(.*?)(\'\).change)/', function ($arrMatches) use ($strInputId)
			{
				return sprintf('%s%s,%s%s', $arrMatches[1], $arrMatches[2], $strInputId, $arrMatches[3]);
			}, $GLOBALS['TL_JQUERY']['mmEditingSubmitOnChange']);

			return;
		}

		$GLOBALS['TL_JQUERY']['mmEditingSubmitOnChange'] = <<<HTML
<script>
	(function ($) {
		$(document).ready(function () {
			$('{$strInputId}').change(function () {
				$(this).parents('form:first')[0].submit();
			});
		});
	})(jQuery);
</script>
HTML;

	}
}
