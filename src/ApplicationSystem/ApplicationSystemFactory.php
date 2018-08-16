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

namespace Richardhj\ContaoFerienpassBundle\ApplicationSystem;


use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem as ApplicationSystemModel;
use Richardhj\ContaoFerienpassBundle\Model\ApplicationSystem;

class ApplicationSystemFactory
{

    /**
     * @return FirstCome
     */
    public static function createFirstCome(): FirstCome
    {
        return new FirstCome(ApplicationSystemModel::findFirstCome());
    }

    /**
     * @return Lot
     */
    public static function createLot(): Lot
    {
        return new Lot(ApplicationSystemModel::findLot());
    }
}
