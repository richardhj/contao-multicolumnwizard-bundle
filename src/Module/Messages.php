<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
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
