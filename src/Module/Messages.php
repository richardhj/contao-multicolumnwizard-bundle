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

namespace Richardhj\ContaoFerienpassBundle\Module;

use Contao\Module;
use Richardhj\ContaoFerienpassBundle\Helper\Message;


/**
 * Class Messages
 * @package  Richardhj\ContaoFerienpassBundle\Module
 */
class Messages extends Module
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_ferienpass_messages';


    /**
     * Return a wildcard in the back end
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $objTemplate = new \BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### '.utf8_strtoupper($GLOBALS['TL_LANG']['FMD'][$this->type][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        // Set a custom template
        if ($this->customTpl != '') {
            $this->strTemplate = $this->customTpl;
        }

        return parent::generate();
    }


    /**
     * Add the messages to the template
     */
    public function compile()
    {
        $this->Template->messages = Message::generate();
    }
}
