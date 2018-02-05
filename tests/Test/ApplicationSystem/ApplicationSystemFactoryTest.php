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

namespace Richardhj\ContaoFerienpassBundle\Test\ApplicationSystem;


use PHPUnit\Framework\TestCase;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\ApplicationSystemFactory;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\ApplicationSystemInterface;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\FirstCome;
use Richardhj\ContaoFerienpassBundle\ApplicationSystem\Lot;

class ApplicationSystemFactoryTest extends TestCase
{

    public function testCreate()
    {
        $applicationSystem = ApplicationSystemFactory::create();
        $this->assertInstanceOf(ApplicationSystemInterface::class, $applicationSystem);
    }

    public function testCreateFirstCome()
    {
        $applicationSystem = ApplicationSystemFactory::createFirstCome();
        $this->assertInstanceOf(FirstCome::class, $applicationSystem);
    }

    public function testCreateLot()
    {
        $applicationSystem = ApplicationSystemFactory::createLot();
        $this->assertInstanceOf(Lot::class, $applicationSystem);
    }
}
