<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 * Copyright (c) 2015-2015 Richard Henkenjohann
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard-ferienpass@henkenjohann.me>
 */

namespace Ferienpass\Module\Items\Offers;

use Contao\Environment;
use Contao\PageModel;
use Ferienpass\Helper\Config;
use MetaModels\FrontendIntegration\Module\ModelList;


/**
 * Class OffersAdministration
 * @package Ferienpass\Module
 */
class Management extends ModelList
{

	/**
	 * Return the item's buttons
	 *
	 * @param  array $arrItem
	 *
	 * @return array
	 */
	public function getButtons($arrItem)
	{
		$arrButtons = array();
		$buttons = array
		(
			'details',
			'edit',
			'applicationlist',
			'delete'
		);

		$objItem = $this
			->getServiceContainer()
			->getFactory()
			->getMetaModel($this
				->getServiceContainer()
				->getFactory()
				->translateIdToMetaModelName($this->metamodel))
			->findById($arrItem['raw']['id']);

		// Disable specific buttons for items with variants
		if ($objItem->isVariantBase() && $objItem->getVariants(null)->getCount())
		{
			unset($buttons[array_search('details', $buttons)]);
			unset($buttons[array_search('applicationlist', $buttons)]);
		}

		// Disable application list if not active
		if (!$objItem->get(Config::get(Config::OFFER_ATTRIBUTE_APPLICATIONLIST_ACTIVE)))
		{
			unset($buttons[array_search('applicationlist', $buttons)]);
		}
		
		//@todo configurable in the backend
		// Disable buttons if ferienpass is live
		unset($buttons[array_search('edit', $buttons)]);
		unset($buttons[array_search('delete', $buttons)]);

		foreach ($buttons as $button)
		{
			$arrButton = array();
			$key = $button . 'Link';

			if (in_array($key, get_class_methods(__CLASS__)))
			{
				$arrButton['link'] = $GLOBALS['TL_LANG']['MSC'][$key][0];
				$arrButton['title'] = $GLOBALS['TL_LANG']['MSC'][$key][1] ?: $arrButton['link'];
				$arrButton['class'] = $button;
				list ($arrButton['href'], $arrButton['attribute']) = $this->$key($arrItem);

				$arrButtons[] = $arrButton;
			}
		}

		return $arrButtons;
	}


	/**
	 * @param  array $arrItem
	 *
	 * @return array
	 */
	protected function detailsLink($arrItem)
	{
		return array($arrItem['jumpTo']['url'], ' data-lightbox=""'); //@todo IF lightbox
	}


	/**
	 * @param  array $arrItem
	 *
	 * @return array
	 */
	protected function editLink($arrItem)
	{
		//@todo configurable
		$strAttribute = '';
		$jumpTo = $this->jumpTo;

		if (!$arrItem['raw']['varbase'])
		{
			$strAttribute = ' data-lightbox="" data-lightbox-iframe="" data-lightbox-reload=""';
			$jumpTo = 36;
		}

		return array($this->generateJumpToLink($jumpTo, $arrItem['raw']['alias']), $strAttribute);
	}


	/**
	 * @param  array $arrItem
	 *
	 * @return array
	 */
	protected function applicationlistLink($arrItem)
	{
		return array($this->generateJumpToLink($this->jumpToApplicationList, $arrItem['raw']['alias']));
	}


	/**
	 * @param  array $arrItem
	 *
	 * @return array
	 */
	protected function deleteLink($arrItem)
	{
		$strHref = $this->addToUrl(sprintf('action=delete::%u::%s', $arrItem['raw']['id'], REQUEST_TOKEN));

		return array($strHref, ' onclick="return confirm(\'' . sprintf($GLOBALS['TL_LANG']['MSC']['itemConfirmDeleteLink'], $arrItem['raw']['name']) . '\')"');
	}


	/**
	 * Create link by given id and item alias
	 *
	 * @param  integer $intPageId
	 * @param  string  $strAlias
	 *
	 * @return string
	 */
	protected function generateJumpToLink($intPageId, $strAlias)
	{
		if ($intPageId < 1)
		{
			return '';
		}

		$strUrl = ampersand(Environment::get('request'), true);

		/** @var \Model\Collection $objTarget */
		$objTarget = PageModel::findByPk($intPageId);

		if ($objTarget !== null)
		{
			$strUrl = ampersand($this->generateFrontendUrl($objTarget->row(), ((\Config::get('useAutoItem') && !\Config::get('disableAlias')) ? '/%s' : '/items/%s')));
		}

		return sprintf($strUrl, $strAlias);
	}
}
