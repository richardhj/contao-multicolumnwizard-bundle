<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2016 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Widget;


use Contao\Widget;


class OfferDate extends \MultiColumnWizard
{

    /** @noinspection PhpMissingParentConstructorInspection
     * @param array|bool $attributes
     */
    public function __construct($attributes = false)
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        Widget::__construct($attributes);
        \System::importStatic('Database');

//        if (TL_MODE == 'FE')
//        {
//            $this->strTemplate = 'form_widget';
//            $this->loadDataContainer($attributes['strTable']);
//        }

        $this->columnFields = [
            'start' => [
                'inputType' => 'text',
                'eval'      => [
                    'rgxp' => 'datim',
                ],
            ],
            'end'   => [
                'inputType' => 'text',
                'eval'      => [
                    'rgxp' => 'datim',
                ],
            ],
        ];
    }

}
