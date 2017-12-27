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

namespace Richardhj\ContaoFerienpassBundle\BackendModule;


use Richardhj\ContaoFerienpassBundle\Model\Config;
use Richardhj\ContaoFerienpassBundle\Model\Offer;


/**
 * Class DataProcessing
 * @package Richardhj\ContaoFerienpassBundle\BackendModule
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

        $objModel = \Richardhj\ContaoFerienpassBundle\Model\DataProcessing::findByPk($intModuleId);

        $arrIds = [];

        if ($objModel->scope == 'single') {
            $strFormSubmit = 'select_items';

            if (\Input::post('FORM_SUBMIT') != $strFormSubmit) {
                $objOffers = Offer::getInstance()->findAll();
                $arrOffers = [];


                /*
                 * Single checkbox
                 */
                while ($objOffers->next()) {
                    $arrOffers[] =
                        [
                            'value' => $objOffers->getItem()->get('id'),
                            'label' => $objOffers->getItem()->get(Config::getInstance()->offer_attribute_name),
                        ];
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
                $this->Template->fieldsets = [
                    [
                        'class'   => 'tl_box',
                        'palette' => $objWidget->generate(),
                    ],
                ];

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
