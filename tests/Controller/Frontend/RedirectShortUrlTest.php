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

namespace Richardhj\ContaoFerienpassBundle\Test\Controller\Frontend;


use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\PageModel;
use MetaModels\Filter\Filter;
use MetaModels\IFactory;
use MetaModels\IMetaModel;
use MetaModels\Render\Setting\IRenderSettingFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Richardhj\ContaoFerienpassBundle\Controller\Frontend\RedirectShortUrl;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RedirectShortUrlTest extends TestCase
{

    private function mockFactory()
    {
        /** @var IFactory|PHPUnit_Framework_MockObject_MockObject $factory */
        $factory = $this->getMockBuilder(IFactory::class)->getMock();

        $factory
            ->expects($this->any())
            ->method('getMetaModel')
            ->will($this->returnValue($this->mockMetaModel()));

        return $factory;
    }

    /**
     * Mock a MetaModel.
     *
     * @return IMetaModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockMetaModel()
    {
        $metaModel = $this->getMockBuilder(IMetaModel::class)->getMock();

        $metaModel
            ->expects($this->any())
            ->method('getTableName')
            ->will($this->returnValue('mm_test'));

        $metaModel
            ->expects($this->any())
            ->method('getEmptyFilter')
            ->will($this->returnValue($this->mockFilter()));

        return $metaModel;
    }

    private function mockFilter()
    {
        /** @var Filter|PHPUnit_Framework_MockObject_MockObject $filter */
        $filter = $this->getMockBuilder(Filter::class)->setConstructorArgs([$this->mockMetaModel()])->getMock();

//        $filter
//            ->expects($this->any())
//            ->method()
//            ->will($this->returnValue());

        return $filter;
    }

    /**
     * @return MockObject|EventDispatcherInterface
     */
    private function mockEventDispatcher()
    {
        return $this->getMockBuilder(EventDispatcherInterface::class)
            ->getMockForAbstractClass();
    }

    /**
     * @return MockObject|IRenderSettingFactory
     */
    private function mockRenderSettings()
    {
        return $this->getMockBuilder(IRenderSettingFactory::class)
            ->getMockForAbstractClass();
    }

    private function getPageId()
    {
        return PageModel::findOneByPublished(true)->id;
    }

    public function testMaliciousId(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        $controller = new RedirectShortUrl($this->mockFactory(), $this->mockRenderSettings(), $this->mockEventDispatcher(), 1, $this->getPageId());
        $controller('wrong.html');
    }

    public function testItemNotFound(): void
    {
        $this->expectException(PageNotFoundException::class);

        $controller = new RedirectShortUrl($this->mockFactory(), $this->mockRenderSettings(), $this->mockEventDispatcher(), 1, $this->getPageId());
        $controller(99);
    }

    public function testRedirectSuccessfully(): void
    {
        $this->expectException(RedirectResponseException::class);

        $factory = $this->mockFactory();

        $controller = new RedirectShortUrl($factory, $this->mockRenderSettings(), $this->mockEventDispatcher(), 1, $this->getPageId());

        $controller(99);
    }
}
