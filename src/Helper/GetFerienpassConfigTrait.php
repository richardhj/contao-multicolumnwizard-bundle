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

namespace Richardhj\ContaoFerienpassBundle\Helper;

use Richardhj\ContaoFerienpassBundle\Model\Config as FerienpassConfig;

trait GetFerienpassConfigTrait
{

    /**
     * @return FerienpassConfig
     */
    private function getFerienpassConfig(): FerienpassConfig {
        return FerienpassConfig::getInstance();
    }
}
