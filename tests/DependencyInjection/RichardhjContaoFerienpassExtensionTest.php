<?php

/**
 * This file is part of richardhj/contao-ferienpass.
 *
 * Copyright (c) 2015-2019 Richard Henkenjohann
 *
 * @package   richardhj/contao-ferienpass
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2015-2019 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-ferienpass/blob/master/LICENSE
 */


namespace Richardhj\ContaoFerienpassBundle\Test\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Richardhj\ContaoFerienpassBundle\DependencyInjection\RichardhjContaoFerienpassExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * This test case test the extension.
 */
class RichardhjContaoFerienpassExtensionTest extends TestCase
{
    /**
     * Test that extension can be instantiated.
     *
     * @return void
     */
    public function testInstantiation(): void
    {
        $extension = new RichardhjContaoFerienpassExtension();

        $this->assertInstanceOf(RichardhjContaoFerienpassExtension::class, $extension);
        $this->assertInstanceOf(ExtensionInterface::class, $extension);
    }
}
