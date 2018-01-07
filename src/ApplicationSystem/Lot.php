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

use Contao\Model\Event\PreSaveModelEvent;
use ContaoCommunityAlliance\DcGeneral\Event\PrePersistModelEvent;
use Richardhj\ContaoFerienpassBundle\Event\UserSetApplicationEvent;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;
use Richardhj\ContaoFerienpassBundle\Model\AttendanceStatus;


/**
 * Class Lot
 *
 * @package Richardhj\ContaoFerienpassBundle\ApplicationSystem
 */
class Lot extends AbstractApplicationSystem
{

}
