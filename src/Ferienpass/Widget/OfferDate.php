<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Widget;

use Contao\Widget;


/**
 * Class OfferDate
 *
 * @package Ferienpass\Widget
 */
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

        $this->disableSorting = true;
        $this->columnFields   = [
            'start' => [
                'label'     => &$GLOBALS['TL_LANG']['MSC']['offer_date']['start'],
                'inputType' => 'text',
                'eval'      => [
                    'rgxp'       => 'datim',
                    'datepicker' => true,
                    'style'      => 'width:150px'
                ],
            ],
            'end'   => [
                'label'     => &$GLOBALS['TL_LANG']['MSC']['offer_date']['end'],
                'inputType' => 'text',
                'eval'      => [
                    'rgxp'       => 'datim',
                    'datepicker' => true,
                    'style'      => 'width:150px'
                ],
            ],
        ];
    }
}
