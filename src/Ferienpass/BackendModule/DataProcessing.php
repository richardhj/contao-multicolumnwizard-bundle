<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\BackendModule;


use Ferienpass\Model\Config;
use Ferienpass\Model\Offer;


/**
 * Class DataProcessing
 * @package Ferienpass\BackendModule
 */
class DataProcessing extends \BackendModule
{

    protected $strTemplate = 'dcbe_general_edit';


    /**
     * Generate the module
     * @return string
     */
    public function generate()
    {
        \System::loadLanguageFile('tl_ferienpass_exportXml');

        if (!\BackendUser::getInstance()->isAdmin) {
            return '<p class="tl_gerror">'.$GLOBALS['TL_LANG']['tl_ferienpass_exportXml']['permission'].'</p>';
        }

        return parent::generate();
    }


    /**
     * Generate the module
     */
    protected function compile()
    {
        $strModule = \Input::get('mod');
        $intModuleId = (int)str_replace('data_processing_', '', $strModule);

        $objModel = \Ferienpass\Model\DataProcessing::findByPk($intModuleId);

        $arrIds = array();

        if ($objModel->scope == 'single') {
            $strFormSubmit = 'select_items';

            if (\Input::post('FORM_SUBMIT') != $strFormSubmit) {
                $objOffers = Offer::getInstance()->findAll();
                $arrOffers = array();


                /*
                 * Single checkbox
                 */
                while ($objOffers->next()) {
                    $arrOffers[] = array
                    (
                        'value' => $objOffers->getItem()->get('id'),
                        'label' => $objOffers->getItem()->get(Config::getInstance()->offer_attribute_name),
                    );
                }

                $buttons[] = sprintf(
                    '<input type="submit" name="start" id="start" class="tl_submit" accesskey="s" value="%s" />',
                    'Export starten'
                );

                /** @noinspection PhpParamsInspection */
                $objWidget = new \CheckBoxWizard();
                $objWidget->name = 'items';
                $objWidget->options = $arrOffers;
                $objWidget->multiple = true;

                $this->Template->subHeadline = 'Angebote zum Export auswählen';
                $this->Template->table = $strFormSubmit;
                $this->Template->editButtons = $buttons;
                $this->Template->fieldsets = array
                (
                    array
                    (
                        'class'   => 'tl_box',
                        'palette' => $objWidget->generate(),
                    ),
                );

                return;
            }

            $arrIds = \Input::post('items');
        }

        $objModel->run($arrIds);

        \Message::addConfirmation(sprintf('Datenverarbeitung "%s" wurde ausgeführt', $objModel->name));

        // Redirect back
        \Controller::redirect(str_replace('&mod='.$strModule, '', \Environment::get('request')));
    }
}
