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

namespace Richardhj\ContaoFerienpassBundle\Module;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\FrontendUser;
use Contao\MemberModel;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Haste\Form\Form;
use MetaModels\IFactory;
use MetaModels\IItem;
use NotificationCenter\Model\Notification;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class InviteHost
 *
 * @package Richardhj\ContaoFerienpassBundle\Module
 */
class HostInviteMember extends AbstractFrontendModuleController
{

    /**
     * The authenticated frontend user.
     *
     * @var FrontendUser
     */
    private $frontendUser;

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
     * HostInviteMember constructor.
     *
     * @param IFactory   $metaModelsFactory The MetaModels factory.
     * @param Connection $connection        The database connection.
     */
    public function __construct(IFactory $metaModelsFactory, Connection $connection)
    {
        $this->frontendUser      = FrontendUser::getInstance();
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
        $hostId    = $this->frontendUser->ferienpass_host;
        $metaModel = $this->metaModelsFactory->getMetaModel('mm_host');
        if (null === $metaModel) {
            throw new \RuntimeException('MetaModel mm_host not found nor initialized!');
        }

        $host = $metaModel->findById($hostId);
        if (null === $host) {
            throw new \RuntimeException('Host not found: ID' . $hostId);
        }

        $coHostsCollection = MemberModel::findBy('ferienpass_host', $hostId);

        $coHosts = [];
        while ($coHostsCollection->next()) {
            if ($coHostsCollection->id === $this->frontendUser->id) {
                continue;
            }

            $coHosts[] = $coHostsCollection->row();
        }

        $template->noCoHosts = empty($coHosts);
        $template->coHosts   = $coHosts;


        $form = new Form(
            'someid', 'POST', function ($haste) {
            return \Input::post('FORM_SUBMIT') === $haste->getFormId();
        }
        );

        $form->addFormField(
            'email',
            [
                'label'     => 'E-Mail-Adresse',
                'inputType' => 'text',
                'eval'      => ['mandatory' => true, 'rgxp' => 'email']
            ]
        );

        $form->addValidator(
            'email',
            function ($email) {
                if (null !== MemberModel::findByEmail($email)) {
                    throw new \Exception('Cannot invite ' . $email);
                }

                return $email;
            }
        );

        $form->addSubmitFormField('submit', 'Einladen');

        if ($form->validate()) {
            $invitedEmail = $form->fetch('email');

            $this->invite($invitedEmail, $model, $host);

            throw new RedirectResponseException($request->getUri());
        }

        $template->form = $form->generate();

        return Response::create($template->parse());
    }

    /**
     * Send the invitation notification.
     *
     * @param string      $email  The invitee email.
     * @param ModuleModel $module The module model.
     * @param IItem       $host   The host.
     */
    private function invite(string $email, $module, IItem $host): void
    {
        /** @var Notification $notification */
        $notification = Notification::findByPk($module->nc_notification);
        if (null === $notification) {
            return;
        }

        $tokens = [];

        $tokens['invitee_email'] = $email;
        $tokens['token']         = $this->createToken((int) $this->frontendUser->id, $email, $host->get('id'));
        $tokens['admin_email']   = $GLOBALS['TL_ADMIN_EMAIL'];

        $jumpTo = PageModel::findByPk($module->jumpTo);
        if (null === $jumpTo) {
            return;
        }

        $tokens['link'] = $jumpTo->getAbsoluteUrl() . '?invite=' . $tokens['token'];

        foreach ($this->frontendUser->getData() as $k => $v) {
            $tokens['member_' . $k] = $v;
        }

        $parsedHost = $host->parseValue();
        foreach ($parsedHost['text'] as $k => $v) {
            $tokens['host_' . $k] = $v;
        }

        $notification->send($tokens);

        $this->addFlash(
            'confirmation',
            sprintf(
                'Eine Einladung zur Mitarbeit wurde an <em>%s</em> gesendet.',
                $email
            )
        );
    }

    /**
     * Create and save a token.
     *
     * @param int    $memberId     The inviting member id.
     * @param string $inviteeEmail The invitee email address.
     * @param int    $hostId       The host id.
     *
     * @return string
     */
    private function createToken(int $memberId, string $inviteeEmail, int $hostId): string
    {
        $token = md5(uniqid(mt_rand(), true));

        $this->connection->createQueryBuilder()
            ->insert('tl_ferienpass_host_invite_token')
            ->values(
                [
                    'tstamp'          => '?',
                    'inviting_member' => '?',
                    'invited_email'   => '?',
                    'token'           => '?',
                    'host'            => '?',
                    'expires'         => '?'
                ]
            )
            ->setParameter(0, time())
            ->setParameter(1, $memberId)
            ->setParameter(2, sha1($inviteeEmail))
            ->setParameter(3, $token)
            ->setParameter(4, $hostId)
            ->setParameter(5, strtotime('+2 days'))
            ->execute();

        return $token;
    }
}
