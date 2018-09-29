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

namespace Richardhj\ContaoFerienpassBundle\Controller\Frontend;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CodeValidate
 *
 * @package Richardhj\ContaoFerienpassBundle\Controller\Frontend
 */
final class CodeValidate
{

    /**
     * The database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * CodeValidate constructor.
     *
     * @param Connection $connection The database connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param Request $request The current request.
     *
     * @return Response
     */
    public function __invoke(Request $request): Response
    {
        if (!$request->isMethod('post')) {
            return Response::create('Request not allowed', Response::HTTP_PRECONDITION_FAILED);
        }

        $data = [];

        $code        = $request->request->get('code') ?? $request->query->get('code');
        $attributeId = $request->request->get('att_id');
        $itemId      = $request->request->get('item_id');

        $expr = $this->connection->getExpressionBuilder();

        $statement = $this->connection->createQueryBuilder()
            ->select('id')
            ->from('tl_ferienpass_code')
            ->where('code=:code')
            ->andWhere(
                $expr->orX()
                    ->add('activated=0')
                    ->add($expr->andX()->add('activated<>0')->add('item_id=:item')->add('att_id=:attr'))
            )
            ->setParameter('code', $code)
            ->setParameter('item', $itemId)
            ->setParameter('attr', $attributeId)
            ->execute();

        $success = (bool) $statement->fetch();

        $data['code']    = $code;
        $data['success'] = true;//$success;

        return JsonResponse::create($data);
    }
}
