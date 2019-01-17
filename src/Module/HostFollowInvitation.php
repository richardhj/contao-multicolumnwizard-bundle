<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2019 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2019 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE proprietary
 */

namespace Richardhj\ContaoFerienpassBundle\Module;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Input;
use Contao\MemberModel;
use Contao\ModuleModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use MetaModels\IFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class HostFollowInvitation
 *
 * This module is intended to place besides the registration module.
 *
 * @package Richardhj\ContaoFerienpassBundle\Module
 */
class HostFollowInvitation extends AbstractFrontendModuleController
{

    /**
     * The MetaModels factory.
     *
     * @var IFactory
     */
    private $metaModelsFactory;

    /**
     * The database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * HostFollowInvitation constructor.
     *
     * @param IFactory   $metaModelsFactory The MetaModels factory.
     * @param Connection $connection        The database connection.
     */
    public function __construct(IFactory $metaModelsFactory, Connection $connection)
    {
        $this->metaModelsFactory = $metaModelsFactory;
        $this->connection        = $connection;
    }

    /**
     * Returns the response.
     *
     * @param Template|object $template The template.
     * @param ModuleModel     $model    The module model.
     * @param Request         $request  The request.
     *
     * @return Response
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $inviteToken = Input::get('invite');
        if (null === $inviteToken) {
            throw new PageNotFoundException('Missing invite token!');
        }

        // Fetch host from invite token.
        $statement = $this->connection->createQueryBuilder()
            ->select('inviting_member', 'host')
            ->from('tl_ferienpass_host_invite_token')
            ->where('token=:token')
            ->andWhere('expires>:time')
            ->setParameter('token', $inviteToken)
            ->setParameter('time', time())
            ->execute();

        if (false === $statement) {
            if (false === (strncmp(Input::get('token'), 'RG', 2) === 0)
                && false === (strncmp(Input::get('token'), 'reg-', 4) === 0)) {
                throw new PageNotFoundException('Request not allowed!');
            }

            return Response::create('');
        }

        $tokenData = $statement->fetch(\PDO::FETCH_OBJ);

        $invitingMember = MemberModel::findByPk($tokenData->inviting_member);
        if (null === $invitingMember) {
            throw new \RuntimeException('Member not found: ID ' . $tokenData->inviting_member);
        }

        $metaModel = $this->metaModelsFactory->getMetaModel('mm_host');
        if (null === $metaModel) {
            throw new \RuntimeException('MetaModel mm_host could not be initialized');
        }

        $host = $metaModel->findById($tokenData->host);
        if (null === $host) {
            throw new \RuntimeException('Host not found: ID' . $tokenData->host);
        }

        $hostNameParsed = $host->parseAttribute('name');

        $template->intro = sprintf(
            'Sie wurden von <span class="person-name">%s</span> zur Mitarbeit an den Ferienpass-Angeboten von <span class="host-name">%s</span> eingeladen.',
            $invitingMember->firstname . ' ' . $invitingMember->lastname,
            $hostNameParsed['text']
        );

        return Response::create($template->parse());
    }
}
