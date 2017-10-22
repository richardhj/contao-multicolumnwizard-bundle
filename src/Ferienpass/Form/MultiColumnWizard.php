<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Form;

use Contao\Controller;
use Contao\Environment;
use Contao\Image;
use Contao\Widget;


/**
 * Class MultiColumnWizard
 * Make the MultiColumnWizard front end executable
 * @property string strCommand
 * @property string currentRecord
 * @package Ferienpass\Form
 */
class MultiColumnWizard extends \MultiColumnWizard
{

    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'form_mcw';


    /**
     * The CSS class prefix
     *
     * @var string
     */
    protected $strPrefix = 'widget widget-mcw';


    /** @noinspection PhpMissingParentConstructorInspection
     * Don't use parent's but parent parent's __construct
     *
     * @param array|null $arrAttributes
     */
    public function __construct($arrAttributes = null)
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Widget::__construct($arrAttributes);
    }


    /**
     * Generate button string
     *
     * @param int $level
     *
     * @return string
     */
    protected function generateButtonString($level = 0)
    {
        $return = '';

        // Add buttons
        foreach ($this->arrButtons as $button => $image) {
            if ($image === false) {
                continue;
            }

            $return .= sprintf
            (
                '<a rel="%s" href="%s" class="widgetImage" title="%s">%s</a> ',
                $button,
                str_replace
                (
                    'index.php',
                    strtok(Environment::get('requestUri'), '?'),
                    Controller::addToUrl
                    (
                        http_build_query
                        (
                            [
                                $this->strCommand => $button,
                                'cid'             => $level,
                                'id'              => $this->currentRecord,
                            ]
                        ),
                        false
                    )
                ),
                $GLOBALS['TL_LANG']['MSC']['tw_r'.specialchars($button)],
                $this->getButtonContent($button) # We don't want to output an image and don't provide $image
            );
        }

        return $return;
    }


    /**
     * Get the content of the button, either text or image
     *
     * @param string $button The button name
     * @param string $image  Provide src path if you want to use image buttons
     *
     * @return string
     */
    protected function getButtonContent($button, $image = '')
    {
        if ($image == '') {
            return '<span class="button '.$button.'"></span>';
        }

        return Image::getHtml(
            $image,
            $GLOBALS['TL_LANG']['MSC']['tw_r'.specialchars($button)],
            'class="tl_listwizard_img"'
        );
    }


    /**
     * Disable the date picker because it is not designed for front end
     *
     * @param string $strId
     * @param string $strKey
     * @param string $rgxp
     *
     * @return string
     */
    protected function getMcWDatePickerString($strId, $strKey, $rgxp)
    {
        return '';
    }
}