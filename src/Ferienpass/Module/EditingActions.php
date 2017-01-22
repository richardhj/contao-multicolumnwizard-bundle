<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
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
use MetaModels\Attribute\Select\MetaModelSelect;


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
                elseif ('mm_ferienpass' !== $this->metaModel->getTableName() ||
                    $this->item->get('host')[MetaModelSelect::SELECT_RAW]['id'] != $this->User->ferienpass_host
                ) {
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

        $this->Template->message = Message::generate();
    }
}
