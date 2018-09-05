<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\Widget;

use Contao\Widget;


/**
 * Class OfferDate
 *
 * @package Richardhj\ContaoFerienpassBundle\Widget
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

        $columnFields = [
            'start'  => [
                'label'     => &$GLOBALS['TL_LANG']['MSC']['offer_date']['start'],
                'inputType' => 'text',
                'eval'      => [
                    'rgxp'  => 'datim',
                    'style' => 'width:150px'
                ],
            ],
            'end'    => [
                'label'     => &$GLOBALS['TL_LANG']['MSC']['offer_date']['end'],
                'inputType' => 'text',
                'eval'      => [
                    'rgxp'  => 'datim',
                    'style' => 'width:150px'
                ],
            ],
        ];

        if ('BE' === TL_MODE) {
            $columnFields['start']['eval']['datepicker'] = true;
            $columnFields['end']['eval']['datepicker']   = true;
        }

        $this->columnFields   = $columnFields;
        $this->disableSorting = true;
    }
}
