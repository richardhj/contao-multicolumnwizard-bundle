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

namespace Richardhj\ContaoFerienpassBundle\Helper;

use Contao\System;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\FirstCome;


/**
 * Class InsertTags
 *
 * @package Richardhj\ContaoFerienpassBundle\Helper
 */
class InsertTags
{

    /**
     * @param string $tag
     *
     * @return string|false
     */
    public function replaceInsertTags($tag)
    {
        $elements = trimsplit('::', $tag);

        if ('ferienpass' === $elements[0]) {
            switch ($elements[1]) {
                case 'max_applications_per_day':
                    /** @var FirstCome $applicationSystem */
                    $applicationSystem = System::getContainer()->get('richardhj.ferienpass.application_system.firstcome');
                    return $applicationSystem->getMaxApplicationsPerDay();
                    break;
            }
        }

        return false;
    }
}
