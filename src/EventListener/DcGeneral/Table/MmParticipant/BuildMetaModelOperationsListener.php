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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmParticipant;


use ContaoCommunityAlliance\DcGeneral\Contao\DataDefinition\Definition\Contao2BackendViewDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\View\Command;
use MetaModels\DcGeneral\Events\MetaModel\BuildMetaModelOperationsEvent;
use Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\AbstractAddAttendancesOperationListener;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

class BuildMetaModelOperationsListener extends AbstractAddAttendancesOperationListener
{

    /**
     * BuildMetaModelOperationsListener constructor.
     */
    public function __construct()
    {
        parent::__construct('mm_participant');
    }
}
