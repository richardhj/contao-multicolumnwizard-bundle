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
	 * @param string $key   The attribute key
	 * @param mixed  $value The attribute value
	 */
	public function __set($key, $value)
	{
		switch ($key)
		{
			case 'placeholder':
				$key = 'data-placeholder'; // for chosen
				$this->arrAttributes[$key] = $value;
				break;

			default:
				parent::__set($key, $value);
				break;
		}
	}


	/**
	 * Check for disabled attribute in option otherwise process default selected procedure
	 *
	 * @param  array $option
	 *
	 * @return string
	 */
	protected function isSelected($option)
	{
		if ($option['disabled'] === true)
		{
			return static::optionDisabled();
		}

		/** @noinspection PhpUndefinedMethodInspection */
		return parent::isSelected($option);
	}


	/**
	 * Return a "disabled" attribute
	 *
	 * @return string The attribute
	 */
	public static function optionDisabled()
	{
		$attribute = ' disabled';

		if ('FE' === TL_MODE)
		{
			/** @var \Contao\PageModel $objPage */
			global $objPage;

			if ('xhtml' === $objPage->outputFormat)
			{
				$attribute = ' disabled="disabled"';
			}
		}

		return $attribute;
	}
}
