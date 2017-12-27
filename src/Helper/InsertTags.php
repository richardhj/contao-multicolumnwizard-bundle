<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Richardhj\ContaoFerienpassBundle\Helper;

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
        global $container;

        $elements = trimsplit('::', $tag);

        if ($elements[0] == 'ferienpass') {
            switch ($elements[1]) {
                case 'max_applications_per_day':
                    /** @var FirstCome $applicationSystem */
                    $applicationSystem = $container['ferienpass.applicationsystem.firstcome'];
                    return $applicationSystem->getModel()->maxApplicationsPerDay;
                    break;
            }
        }

        return false;
    }
}
