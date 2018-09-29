<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2018 Richard Henkenjohann
 *
 * @package   richardhj/richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2018 Richard Henkenjohann
 * @license   https://github.com/richardhj/richardhj/contao-ferienpass/blob/master/LICENSE
 */

namespace Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\FerienpassCode;

use Doctrine\DBAL\Connection;
use MetaModels\Attribute\AbstractAttributeTypeFactory;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class AttributeTypeFactory
 *
 * @package MetaModels\Attribute\FerienpassCode
 */
final class AttributeTypeFactory extends AbstractAttributeTypeFactory
{

    /**
     * Database connection.
     *
     * @var Connection
     */
    private $connection;

    /**
     * The translator.
     *
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * {@inheritDoc}
     */
    public function __construct(Connection $connection, TranslatorInterface $translator)
    {
        parent::__construct();

        $this->connection = $connection;
        $this->typeName   = 'ferienpass_code';
        $this->typeIcon   = 'bundles/richardhjcontaoferienpass/img/attribute_code.svg';
        $this->typeClass  = FerienpassCode::class;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function createInstance($information, $metaModel)
    {
        return new $this->typeClass($metaModel, $information, $this->connection, $this->translator);
    }
}
