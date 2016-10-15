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
     * Check whether an input is one of the given options
     *
     * @param mixed $input The input string or array
     *
     * @return boolean True if the selected option exists
     */
    protected function isValidOption($input)
    {
        if (false === parent::isValidOption($input)) {
            return false;
        }

        if (!is_array($input)) {
            $input = [$input];
        }

        // Check each option
        foreach ($input as $strInput) {
            foreach ($this->arrOptions as $v) {
                // Single dimensional array
                if (array_key_exists('value', $v)) {
                    if ($strInput == $v['value'] && $v['disabled']) {
                        return false;
                    }
                } // Multi-dimensional array
                else {
                    foreach ($v as $vv) {
                        if ($strInput == $vv['value'] && $vv['disabled']) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
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
