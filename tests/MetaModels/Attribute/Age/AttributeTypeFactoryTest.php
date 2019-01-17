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


namespace Richardhj\ContaoFerienpassBundle\Test\MetaModels\Attribute\Age;

use Doctrine\DBAL\Connection;
use MetaModels\IMetaModel;
use PHPUnit\Framework\TestCase;
use Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\Age\Age;
use Richardhj\ContaoFerienpassBundle\MetaModels\Attribute\Age\AttributeTypeFactory;


class AttributeTypeFactoryTest extends TestCase
{
    /**
     * Mock a MetaModel.
     *
     * @param string $tableName        The table name.
     *
     * @param string $language         The language.
     *
     * @param string $fallbackLanguage The fallback language.
     *
     * @return IMetaModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private function mockMetaModel($tableName, $language, $fallbackLanguage)
    {
        $metaModel = $this->getMockBuilder(IMetaModel::class)->getMock();

        $metaModel
            ->expects($this->any())
            ->method('getTableName')
            ->will($this->returnValue($tableName));

        $metaModel
            ->expects($this->any())
            ->method('getActiveLanguage')
            ->will($this->returnValue($language));

        $metaModel
            ->expects($this->any())
            ->method('getFallbackLanguage')
            ->will($this->returnValue($fallbackLanguage));

        return $metaModel;
    }

    /**
     * Mock the database connection.
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function mockConnection()
    {
        return $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * Test creation of an age attribute.
     *
     * @return void
     */
    public function testCreateAge(): void
    {
        $connection = $this->mockConnection();

        $factory   = new AttributeTypeFactory($connection);
        $values    = [
            // We don't have custom settings here yet.
            //            'force_alias'  => '',
            //            'alias_fields' => serialize(['title'])
        ];
        $attribute = $factory->createInstance(
            $values,
            $this->mockMetaModel('mm_test', 'de', 'en')
        );

        $check = $values;

        $this->assertInstanceOf(Age::class, $attribute);

        foreach ($check as $key => $value) {
            $this->assertEquals($value, $attribute->get($key), $key);
        }
    }
}
