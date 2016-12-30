<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Module;

use Contao\Controller;
use Contao\Environment;
use Contao\Input;
use Contao\PageModel;
use Contao\RequestToken;
use Ferienpass\Helper\Message;


/**
 * Class EditingActions
 * @package Ferienpass\Module
 */
class EditingActions extends Items
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_items_editing_actions';


    /**
     * Return a wildcard in the back end
     *
     * @return string
     */
    public function generate()
    {
        // Load language file
        Controller::loadLanguageFile('exception');

        return parent::generate();
    }


    /**
     * Generate the module
     */
    protected function compile()
    {
        /** @var \Contao\PageModel $objPage */
        global $objPage;

        /*
         * Process link actions
         */
        // Delete item
        if ('delete' === substr(Input::get('action'), 0, 6)) {
            list(, $id, $rt) = trimsplit('::', Input::get('action'));

            if (RequestToken::validate($rt)) {
                //@todo check for notDeletable metamodel
                // Does not work because getInputScreenDetails() creates a instance of the default input screen and conditions are not supported in frontend yet
//				$viewCombinations = new ViewCombinations($this->objMetaModel->getServiceContainer(), $this->User);
//				$inputScreen = $viewCombinations->getInputScreenDetails($this->objMetaModel->getTableName());
//				$inputScreen->isDeletable();

                // Get target item and owner attribute
                $this->fetchItem($id);
                $this->fetchOwnerAttribute();

                if (null === $this->item) {
                    Message::addError($GLOBALS['TL_LANG']['XPT']['itemDeleteNotFound']);
                } // Do permission check
                elseif ($this->item->get($this->ownerAttribute->getColName())['id'] != $this->User->id) {
                    Message::addError($GLOBALS['TL_LANG']['XPT']['itemDeleteMissingPermission']);
                } // Delete
                else {
                    $this->metaModel->delete($this->item);

                    Message::addConfirmation($GLOBALS['TL_LANG']['MSC']['itemDeleteConfirmation']);
                }
            } else {
                Message::addError($GLOBALS['TL_LANG']['XPT']['tokenRetry']);
            }
        }

        /*
         * Generate button
         */
        $url = $this->getEditLink();

        $this->linkTitle = $this->linkTitle ?: $url;

//        $this->Template->attribute = $this->rel ? ' data-lightbox="'.substr(
//                $this->rel,
//                9,
//                -1
//            ).'" data-lightbox-reload="" data-lightbox-iframe=""' : ''; //@todo
        $this->Template->href = $url;
        $this->Template->link = $this->linkTitle;
        $this->Template->linkTitle = specialchars($this->titleText ?: $this->linkTitle);
        $this->Template->target = '';
        $this->Template->message = Message::generate();

        // Override the link target
        if ($this->target) {
            $this->Template->target = ($objPage->outputFormat == 'xhtml') ? ' onclick="return !window.open(this.href)"' : ' target="_blank"';
        }
    }


    /**
     * Generate and return the edit link
     *
     * @param  string $alias
     *
     * @return string
     */
    protected function getEditLink($alias = '')
    {
        if ($this->jumpTo < 1) {
            return '';
        }

        $url = ampersand(Environment::get('request'), true);

        /** @type \Model\Collection $target */
        $target = PageModel::findByPk($this->jumpTo);

        if (null !== $target) {
            $url = ampersand(
                $this->generateFrontendUrl(
                    $target->row(),
                    ((\Config::get('useAutoItem') && !\Config::get('disableAlias')) ? '/%s' : '/items/%s')
                )
            );
        }

        return rtrim(sprintf($url, $alias), '/');
    }
}
