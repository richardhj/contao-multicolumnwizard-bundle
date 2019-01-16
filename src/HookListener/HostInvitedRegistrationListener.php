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

namespace Richardhj\ContaoFerienpassBundle\HookListener;

use Contao\Input;
use Contao\MemberModel;
use Doctrine\DBAL\Connection;

/**
 * Class HostInvitedRegistrationListener
 *
 * @package Richardhj\ContaoFerienpassBundle\HookListener
 */
class HostInvitedRegistrationListener
{

    /**
     * The database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * HostInvitedMemberListener constructor.
     *
     * @param Connection $connection The database connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Check for an invite token, assign the member to the host.
     *
     * @param int   $newUserId  The id of the new invited member.
     * @param array $memberData The data of the member.
     */
    public function onCreateNewUser($newUserId, $memberData): void
    {
        $inviteToken = Input::get('invite');
        if (null === $inviteToken) {
            return;
        }

        // Fetch host from invite token.
        $statement = $this->connection->createQueryBuilder()
            ->select('host')
            ->from('tl_ferienpass_host_invite_token')
            ->where('token=:token')
            //->andWhere('invited_email=:email')
            ->andWhere('expires>:time')
            ->setParameter('token', $inviteToken)
            //->setParameter('email', sha1($memberData['email']))
            ->setParameter('time', time())
            ->execute();

        if (false === $statement) {
            return;
        }

        $memberModel = MemberModel::findByPk($newUserId);
        if (null === $memberModel) {
            throw new \RuntimeException('Member not found: ID' . $newUserId);
        }

        // Assign host.
        $memberModel->ferienpass_host = $statement->fetchColumn();
        $memberModel->save();

        // Invalidate token.
        $this->connection->createQueryBuilder()
            ->delete('tl_ferienpass_host_invite_token')
            ->where('token=:token')
            ->setParameter('token', $inviteToken);
    }
}
