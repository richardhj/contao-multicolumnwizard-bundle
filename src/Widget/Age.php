<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Richardhj\ContaoFerienpassBundle\Widget;

use Contao\Widget;


/**
 * Class Age
 * @package Richardhj\ContaoFerienpassBundle\Widget
 */
class Age extends Widget
{

    /**
     * Submit user input
     *
     * @var boolean
     */
    protected $blnSubmitInput = true;


    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'be_widget_fpage';


    /**
     * The widget's lines
     *
     * @var array
     */
    protected $arrWidgetLines = [];


    /**
     * The widget's lines parsed
     *
     * @var array
     */
    protected $arrOptionsParsed = [];


    /**
     * The checked line or false if none set
     *
     * @var integer|false
     */
    protected $intCheckedLine;


    /**
     * Get specific attribute
     *
     * @param string $strKey
     *
     * @return mixed
     */
    public function __get($strKey)
    {
        switch ($strKey) {
            case 'options_parsed':
                if (empty($this->arrOptionsParsed)) {
                    $this->generateParsedOptions();
                }

                return $this->arrOptionsParsed;
                break;

            case 'checked_line':
                if (!isset($this->intCheckedLine)) {
                    $this->findCheckedLine();
                }

                return $this->intCheckedLine;
                break;
        }

        return parent::__get($strKey);
    }


    /**
     * Add specific attributes
     *
     * @param string $strKey
     * @param mixed  $varValue
     */
    public function __set($strKey, $varValue)
    {
        switch ($strKey) {
            case 'mandatory':
                if ($varValue) {
                    $this->arrAttributes['required'] = 'required';
                } else {
                    unset($this->arrAttributes['required']);
                }
                parent::__set($strKey, $varValue);
                break;

            case 'widget_lines':
                $this->arrWidgetLines = deserialize($varValue);
                break;

            default:
                parent::__set($strKey, $varValue);
                break;
        }
    }


    /**
     * Generate each widget line as parsed string in $arrOptions
     */
    protected function generateParsedOptions()
    {
        foreach ($this->arrWidgetLines as $i => $arrOption) {
            if (strpos($arrOption['input_format'], '</label>') !== false) {
                $arrOption['input_format'] .= '</label>';
            }

            $arrLineInputs = [];
            $arrLineInputValues = [];

            if ($i === $this->checked_line) {
                $arrLineInputValues = array_values(array_filter(trimsplit(',', $this->varValue)));
            }

            for ($ii = 0; $ii < substr_count($arrOption['input_format'], '%s'); $ii++) {
                $arrLineInputs[] = sprintf(
                    '<input type="text" name="%s" id="ctrl_%s" class="%s" value="%s"%s onfocus="Backend.getScrollOffset()">',
                    $this->strName.'[values]['.$i.']['.$ii.']',
                    $this->strId.'_'.$i.'_'.$ii,
                    (TL_MODE == 'BE') ? 'tl_text' : 'text',
                    !empty($arrLineInputValues) ? $arrLineInputValues[$ii] : '',
                    (TL_MODE == 'BE') ? ' style="width: 18px;text-align: center"' : ''
                );
            }

            $strInputFields = vsprintf($arrOption['input_format'], $arrLineInputs);

            $this->arrOptionsParsed[] = sprintf(
                '<input type="radio" name="%s" id="opt_%s" class="tl_radio" value="%s"%s%s onfocus="Backend.getScrollOffset()"> <label for="opt_%s">%s',
                $this->strName.'[line]',
                $this->strId.'_'.$i,
                $i,
                $this->isChecked($i),
                $this->getAttributes(),
                $this->strId.'_'.$i,
                $strInputFields
            );
        }
    }


    /**
     * Check whether an option is checked
     *
     * @param int $intCurrentLine The current line
     *
     * @return string The "checked" attribute or an empty string
     */
    protected function isChecked($intCurrentLine)
    {
        // Mark default option as checked
        if ($this->checked_line === false && $this->arrWidgetLines[$intCurrentLine]['default']) {
            return static::optionChecked(1, 1);
        }

        return static::optionChecked($intCurrentLine, $this->checked_line);
    }


    /**
     * Get widget's checked line
     */
    protected function findCheckedLine()
    {
        $intCheckedLine = null;
        $arrWidgetLines = $this->arrWidgetLines;

        foreach ($arrWidgetLines as $i => $arrWidgetLine) {
            $strDerivedSaveFormat = preg_replace('/[1-9][0-9]*/', '%s', $this->varValue);

            if ($strDerivedSaveFormat == $arrWidgetLine['save_format']) {
                $intCheckedLine = $i;
                break;
            }
        }

        $this->intCheckedLine = ($intCheckedLine !== null) ? $intCheckedLine : false;
    }


    /**
     * Check for a valid option and save data
     */
    public function validate()
    {
        $arrSubmit = $this->getPost($this->strName);
        $intSelectedLine = (int)$arrSubmit['line'];
        $arrWidgetLine = $this->arrWidgetLines[$intSelectedLine];
        $intRequestedInputs = substr_count($arrWidgetLine['input_format'], '%s');
        $arrLineInputs = [];

        for ($i = 0; $i < $intRequestedInputs; $i++) {
            $value = $arrSubmit['values'][$intSelectedLine][$i];

            // Check for missing value
            if (empty($value)) {
                $this->addError(
                    sprintf(
                        $GLOBALS['TL_LANG']['ERR']['ageInputMissingValues'],
                        str_replace('%s', '<em>x</em>', $arrWidgetLine['render_format'])
                    )
                );
            }

            // Check for natural values
            if (!\Validator::isNatural($value)) {
                $this->addError($GLOBALS['TL_LANG']['ERR']['natural']);
            }

            // Check for reverse age ranges
            if ($i > 0) {
                $preValue = $arrSubmit['values'][$intSelectedLine][$i - 1];

                if ($value <= $preValue) {
                    $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['ageInputReverseAgeRanges'], $value, $preValue));
                }
            }

            $arrLineInputs[] = $value;
        }

        $varValue = vsprintf($arrWidgetLine['save_format'], $arrLineInputs);
        $varValue = $this->validator($varValue);

        if ($this->hasErrors()) {
            $this->class = 'error';
        }

        $this->varValue = $varValue;
    }


    /**
     * Generate the widget and return it as string
     *
     * @return string
     */
    public function generate()
    {
        $arrOptions = $this->options_parsed;

        // Add wrapping fieldset
        return sprintf(
            '<fieldset id="ctrl_%s" class="tl_radio_container%s"><legend>%s%s%s%s</legend>%s</fieldset>%s',
            $this->strId,
            (($this->strClass != '') ? ' '.$this->strClass : ''),
            ($this->mandatory ? '<span class="invisible">'.$GLOBALS['TL_LANG']['MSC']['mandatory'].'</span> ' : ''),
            $this->strLabel,
            ($this->mandatory ? '<span class="mandatory">*</span>' : ''),
            $this->xlabel,
            implode('<br>', $arrOptions),
            $this->wizard
        );
    }
}
