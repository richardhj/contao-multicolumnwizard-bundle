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

namespace Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmHost;

use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\Input;
use ContaoCommunityAlliance\DcGeneral\Contao\DataDefinition\Definition\Contao2BackendViewDefinitionInterface;
use ContaoCommunityAlliance\DcGeneral\Data\ModelId;
use ContaoCommunityAlliance\DcGeneral\DataDefinition\Definition\View\Command;
use MetaModels\DcGeneral\Events\MetaModel\BuildMetaModelOperationsEvent;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class SwitchToMembersOperationButtonListener
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\DcGeneral\Table\MmHost
 */
class SwitchToMembersOperationButtonListener
{

    /**
     * The session.
     *
     * @var SessionInterface
     */
    private $session;

    /**
     * The router.
     *
     * @var RouterInterface
     */
    private $router;

    /**
     * SwitchToMembersOperationButtonListener constructor.
     *
     * @param SessionInterface $session The session.
     * @param RouterInterface  $router  The router.
     */
    public function __construct(SessionInterface $session, RouterInterface $router)
    {
        $this->session = $session;
        $this->router  = $router;
    }

    /**
     * Add the "switch to members" operation to the MetaModel back end view.
     *
     * @param BuildMetaModelOperationsEvent $event The event.
     *
     * @return void
     */
    public function addOperation(BuildMetaModelOperationsEvent $event): void
    {
        $metaModel = $event->getMetaModel();
        if ('mm_host' !== $metaModel->getTableName()) {
            return;
        }

        $this->checkRequest();

        /** @var Contao2BackendViewDefinitionInterface $view */
        $view          = $event->getContainer()->getDefinition(Contao2BackendViewDefinitionInterface::NAME);
        $collection    = $view->getModelCommands();
        $operationName = 'switch_to_members';

        $command = new Command();
        $command->setName($operationName);

        $parameters = $command->getParameters();

        $parameters['key'] = 'switch_to_members';

        if (!$command->getLabel()) {
            $command->setLabel($operationName . '.0');
        }
        if (!$command->getDescription()) {
            $command->setDescription($operationName . '.1');
        }

        $extra = $command->getExtra();

        $extra['icon']    = 'bundles/richardhjcontaoferienpass/img/switch_to_members.svg';
        $extra['idparam'] = 'id';

        $collection->addCommand($command);
    }

    /**
     * Check for request and redirect to members page.
     *
     * @return void
     */
    private function checkRequest(): void
    {
        if ('switch_to_members' !== Input::get('key')) {
            return;
        }

        $modelId = ModelId::fromSerialized(Input::get('id'));

        /** @var AttributeBagInterface $sessionBag */
        $sessionBag = $this->session->getBag('contao_backend');

        $session = $sessionBag->all();

        $session['filter']['tl_member']['ferienpass_host'] = $modelId->getId();

        $sessionBag->replace($session);

        throw new RedirectResponseException($this->router->generate('contao_backend', ['do' => 'member']));
    }
}
