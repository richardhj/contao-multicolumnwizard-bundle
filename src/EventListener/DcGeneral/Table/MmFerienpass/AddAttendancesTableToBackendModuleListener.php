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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmFerienpass;

use Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\AbstractAddAttendancesTableToBackendModuleListener;

class AddAttendancesTableToBackendModuleListener extends AbstractAddAttendancesTableToBackendModuleListener
{

    /**
     * @return void
     *
     * @internal param MetaModelsBootEvent $event
     */
    public function handle(): void
    {
        $this->addTableForModule('mm_ferienpass');
    }
}
