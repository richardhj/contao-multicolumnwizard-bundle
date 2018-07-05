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


/**
 * Class OfferDate
 *
 * @package Richardhj\ContaoFerienpassBundle\Form
 */
class OfferDate extends \Richardhj\ContaoFerienpassBundle\Widget\OfferDate
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

            $return .= sprintf(
                '<a data-operations="%s" href="%s" class="widgetImage" title="%s">%s</a> ',
                $button,
                str_replace(
                    'index.php',
                    strtok(\Environment::get('requestUri'), '?'),
                    \Controller::addToUrl(
                        http_build_query(
                            [
                                $this->strCommand => $button,
                                'cid'             => $level,
                                'id'              => $this->currentRecord,
                            ]
                        ),
                        false
                    )
                ),
                $GLOBALS['TL_LANG']['MSC']['tw_r' . specialchars($button)],
                $this->getButtonContent($button) # We don't want to output an image and don't provide $image
            );
        }

        return $return;
    }


    /**
     * Get the content of the button, either text or image
     *
     * @param string $button The button name
     *
     * @return string
     */
    protected function getButtonContent($button)
    {
        return '<span class="button ' . $button . '"></span>';
    }
}
