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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmFerienpass;


use Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\AbstractAddAttendancesOperationListener;

/**
 * Class BuildMetaModelOperationsListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmFerienpass
 */
class BuildMetaModelOperationsListener extends AbstractAddAttendancesOperationListener
{

    /**
     * BuildMetaModelOperationsListener constructor.
     */
    public function __construct()
    {
        parent::__construct('mm_ferienpass');
    }
}
