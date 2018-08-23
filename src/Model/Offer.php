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

namespace Richardhj\ContaoFerienpassBundle\Model;

use Contao\System;
use MetaModels\Factory;
use MetaModels\IItem;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\ApplicationSystemInterface;
use Richardhj\ContaoFerienpassBundle\Entity\PassEdition;
use Symfony\Bridge\Doctrine\ManagerRegistry;


/**
 * Class Offer
 *
 * @package Richardhj\ContaoFerienpassBundle\Model
 */
class Offer extends AbstractSimpleMetaModel
{

    /**
     * @var ManagerRegistry
     */
    private $doctrine;

    /**
     * Offer constructor.
     *
     * @param Factory $factory
     *
     * @throws \RuntimeException
     */
    public function __construct(Factory $factory)
    {
        parent::__construct($factory, 'mm_ferienpass');
        $this->doctrine = System::getContainer()->get('doctrine');
    }

    /**
     * @param IItem $offer
     *
     * @return ApplicationSystemInterface|null
     */
    public function getApplicationSystem(IItem $offer): ?ApplicationSystemInterface
    {
        $reference   = $offer->get('pass_edition');
        $passEdition = $this->doctrine->getRepository(PassEdition::class)->find($reference['id']);

        return $passEdition->getCurrentApplicationSystem();
    }
}
