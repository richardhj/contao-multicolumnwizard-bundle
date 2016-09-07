<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Form;

use Contao\FormSelectMenu;


/**
 * Class SelectDisabledOptions
 * A regular form select with using the select function for the disabled tag
 *
 * @package Ferienpass\Form
 */
class SelectDisabledOptions extends FormSelectMenu
{

	/**
	 * Allow the placeholder attribute
	 *
	 * @param string $strKey   The attribute key
	 * @param mixed  $varValue The attribute value
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'placeholder':
				$strKey = 'data-placeholder'; // for chosen
				$this->arrAttributes[$strKey] = $varValue;
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}


	/**
	 * Check for disabled attribute in option otherwise process default selected procedure
	 *
	 * @param  array $arrOption
	 *
	 * @return string
	 */
	protected function isSelected($arrOption)
	{
		if ($arrOption['disabled'] === true)
		{
			return static::optionDisabled();
		}

		/** @noinspection PhpUndefinedMethodInspection */
		return parent::isSelected($arrOption);
	}


	/**
	 * Return a "disabled" attribute
	 *
	 * @return string The attribute
	 */
	public static function optionDisabled()
	{
		$attribute = ' disabled';

		if (TL_MODE == 'FE')
		{
			/** @var \Contao\PageModel $objPage */
			global $objPage;

			if ($objPage->outputFormat == 'xhtml')
			{
				$attribute = ' disabled="disabled"';
			}
		}

		return $attribute;
	}
}
