<?php

namespace Ferienpass\Helper;

use Ferienpass\Model\Config as FerienpassConfig;


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
                    return FerienpassConfig::getInstance()->max_applications_per_day;
                    break;
            }
        }

        return false;
    }
}
