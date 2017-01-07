<?php
/**
 * FERIENPASS extension for Contao Open Source CMS built on the MetaModels extension
 *
 * Copyright (c) 2015-2017 Richard Henkenjohann
 *
 * @package Ferienpass
 * @author  Richard Henkenjohann <richard@ferienpass.online>
 */

namespace Ferienpass\Helper;


/**
 * Class InsertTags
 * @package Ferienpass\Helper
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

        if ($elements[0] == 'ferienpass') {
            switch ($elements[1]) {
                case 'max_applications_per_day':
                    return ''; // todo
                    break;
            }
        }

        return false;
    }
}
