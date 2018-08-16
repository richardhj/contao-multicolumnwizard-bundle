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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table;


use ContaoCommunityAlliance\DcGeneral\Contao\DataDefinition\Definition\Contao2BackendViewDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\View\Command;
use MetaModels\DcGeneral\Events\MetaModel\BuildMetaModelOperationsEvent;
use Richardhj\ContaoFerienpassBundle\Model\Attendance;

abstract class AbstractAddAttendancesOperationListener
{

    /**
     * @var string
     */
    private $table;

    /**
     * AbstractAddAttendancesOperationListener constructor.
     *
     * @param string $table The table name.
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Add the "edit attendances" operation to the MetaModel back end view
     *
     * @param BuildMetaModelOperationsEvent $event The event.
     */
    public function handle(BuildMetaModelOperationsEvent $event): void
    {
        if ($this->table !== $event->getMetaModel()->getTableName()) {
            return;
        }

        /** @var Contao2BackendViewDefinitionInterface $view */
        $view          = $event->getContainer()->getDefinition(Contao2BackendViewDefinitionInterface::NAME);
        $collection    = $view->getModelCommands();
        $operationName = 'edit_attendances';

        $command = new Command();
        $command->setName($operationName);

        $parameters          = $command->getParameters();
        $parameters['table'] = Attendance::getTable();

        if (!$command->getLabel()) {
            $command->setLabel($operationName.'.0');
        }
        if (!$command->getDescription()) {
            $command->setDescription($operationName.'.1');
        }

        $extra            = $command->getExtra();
        $extra['icon']    = 'bundles/richardhjcontaoferienpass/img/edit_attendances.svg';
        $extra['idparam'] = 'pid';

        $collection->addCommand($command);
    }
}
