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

namespace Richardhj\ContaoFerienpassBundle\EventListener\PrivacyConsent;

use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendUser;
use Contao\ModuleModel;
use Contao\PageModel;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class HostAddAlert
 *
 * @package Richardhj\ContaoFerienpassBundle\EventListener\PrivacyConsent
 */
final class HostAddAlert
{
    /**
     * The session.
     *
     * @var Session
     */
    private $session;

    /**
     * The Contao framework.
     *
     * @var ContaoFramework
     */
    private $contaoFramework;

    /**
     * The database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * HostAddAlert constructor.
     *
     * @param Session         $session         The session.
     * @param ContaoFramework $contaoFramework The contao framework.
     * @param Connection      $connection      The database connection.
     */
    public function __construct(Session $session, ContaoFramework $contaoFramework, Connection $connection)
    {
        $this->session         = $session;
        $this->contaoFramework = $contaoFramework;
        $this->connection      = $connection;
    }

    /**
     * Add flash message that reminds the host to sign the privacy statement.
     *
     * @return void
     */
    public function onGeneratePage(): void
    {
        $user = $this->contaoFramework->createInstance(FrontendUser::class);

        // Is member a host?
        if (false === $user->isMemberOf(1)) {
            return;
        }

        // Is already signed?
        $statement = $this->connection->createQueryBuilder()
            ->select('tstamp')
            ->from('tl_ferienpass_host_privacy_consent')
            ->where('member=:member')
            ->andWhere('type="sign"')
            ->setParameter('member', $user->id)
            ->setMaxResults(1)
            ->orderBy('tstamp', 'DESC')
            ->execute();

        $signed = $statement->rowCount() > 0;
        if (true === $signed) {
            return;
        }

        // Find singing module and corresponding page.
        $module = ModuleModel::findOneByType('host_privacy_consent');
        if (null === $module) {
            return;
        }

        $contentElement = ContentModel::findOneByModule($module->id);
        if (null === $contentElement) {
            return;
        }

        $article = ArticleModel::findByPk($contentElement->pid);
        if (null === $article) {
            return;
        }

        $page = PageModel::findByPk($article->pid);
        if (null === $page) {
            return;
        }

        // Add flash.
        $this->session->getFlashBag()->add(
            'warning',
            'Bitte unterzeichnen Sie die Datenschutzerklärung. <a class="alert__btn btn btn--primary" href="' . $page->getFrontendUrl()
            . '">hier unterzeichnen</a>'
        );
    }
}
