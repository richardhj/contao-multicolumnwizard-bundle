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

use Contao\Controller;
use Contao\Environment;
use Contao\Input;
use Contao\PageModel;
use Contao\RequestToken;
use Ferienpass\Helper\Message;
use Ferienpass\Module\Items;


/**
 * Class EditingActions
 * @package Ferienpass\Module
 */
class EditingActions extends Items
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_items_editing_actions';


	/**
	 * Return a wildcard in the back end
	 *
	 * @return string
	 */
	public function generate()
	{
		// Load language file
		Controller::loadLanguageFile('exception');

		return parent::generate();
	}


	/**
	 * Generate the module
	 */
	protected function compile()
	{
		/** @var \Contao\PageModel $objPage */
		global $objPage;

		/*
		 * Process link actions
		 */
		// Delete item
		if (substr(Input::get('action'), 0, 6) == 'delete')
		{
			list(, $id, $rt) = trimsplit('::', Input::get('action'));

			if (RequestToken::validate($rt))
			{
				//@todo check for notDeletable metamodel
				// Does not work because getInputScreenDetails() creates a instance of the default input screen and conditions are not supported in frontend yet
//				$viewCombinations = new ViewCombinations($this->objMetaModel->getServiceContainer(), $this->User);
//				$inputScreen = $viewCombinations->getInputScreenDetails($this->objMetaModel->getTableName());
//				$inputScreen->isDeletable();

				// Get target item and owner attribute
				$this->fetchItem($id);
				$this->fetchOwnerAttribute();

				if (null === $this->objItem)
				{
					Message::addError($GLOBALS['TL_LANG']['XPT']['itemDeleteNotFound']);
				}
				// Do permission check
				elseif ($this->objItem->get($this->objOwnerAttribute->getColName())['id'] != $this->User->id)
				{
					Message::addError($GLOBALS['TL_LANG']['XPT']['itemDeleteMissingPermission']);
				}
				// Delete
				else
				{
					$this->objMetaModel->delete($this->objItem);

					Message::addConfirmation($GLOBALS['TL_LANG']['MSC']['itemDeleteConfirmation']);
				}
			}
			else
			{
				Message::addError($GLOBALS['TL_LANG']['XPT']['tokenRetry']);
			}
		}

		/*
		 * Generate button
		 */
		$strUrl = $this->getEditLink();

		if ($this->linkTitle == '')
		{
			$this->linkTitle = $strUrl;
		}

		$this->Template->attribute = $this->rel ? ' data-lightbox="'. substr($this->rel, 9, -1) .'" data-lightbox-reload="" data-lightbox-iframe=""' : ''; //@todo
		$this->Template->href = $strUrl;
		$this->Template->link = $this->linkTitle;
		$this->Template->linkTitle = specialchars($this->titleText ?: $this->linkTitle);
		$this->Template->target = '';
		$this->Template->message = Message::generate();

		// Override the link target
		if ($this->target)
		{
			$this->Template->target = ($objPage->outputFormat == 'xhtml') ? ' onclick="return !window.open(this.href)"' : ' target="_blank"';
		}
	}


	/**
	 * Generate and return the edit link
	 *
	 * @param  string $strAlias
	 *
	 * @return string
	 */
	protected function getEditLink($strAlias='')
	{
		if ($this->jumpTo < 1)
		{
			return '';
		}

		$strUrl = ampersand(Environment::get('request'), true);

		/** @type \Model\Collection $objTarget */
		$objTarget = PageModel::findByPk($this->jumpTo);

		if ($objTarget !== null)
		{
			$strUrl = ampersand($this->generateFrontendUrl($objTarget->row(), ((\Config::get('useAutoItem') && !\Config::get('disableAlias')) ? '/%s' : '/items/%s')));
		}

		return rtrim(sprintf($strUrl, $strAlias), '/');
	}
}
