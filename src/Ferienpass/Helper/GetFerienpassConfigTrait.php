<?php
/**
 * Created by PhpStorm.
 * User: richard
 * Date: 01.06.17
 * Time: 18:05
 */

namespace Ferienpass\Helper;

use Ferienpass\Model\Config as FerienpassConfig;

trait GetFerienpassConfigTrait
{

    /**
     * @return FerienpassConfig
     */
    private function getFerienpassConfig(): FerienpassConfig {
        return FerienpassConfig::getInstance();
    }
}
