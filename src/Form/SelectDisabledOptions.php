<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\Form;

use Contao\FormSelectMenu;


/**
 * Class SelectDisabledOptions
 * A regular form select with using the select function for the disabled tag
 *
 * @package Richardhj\ContaoFerienpassBundle\Form
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
        switch ($key) {
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
        if ($option['disabled'] === true) {
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

        if ('FE' === TL_MODE) {
            /** @var \Contao\PageModel $objPage */
            global $objPage;

            if ('xhtml' === $objPage->outputFormat) {
                $attribute = ' disabled="disabled"';
            }
        }

        return $attribute;
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
        /** @noinspection PhpUndefinedMethodInspection */
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
}
