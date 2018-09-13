<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\ApplicationSystem;


/**
 * Class FirstCome
 *
 * @package Richardhj\ContaoFerienpassBundle\ApplicationSystem
 */
class FirstCome extends AbstractApplicationSystem
{

    /**
     * Get the maximum number of applications per participant and day.
     *
     * @return int
     */
    public function getMaxApplicationsPerDay(): int
    {
        return (int)$this->getModel()->maxApplicationsPerDay;
    }
}
