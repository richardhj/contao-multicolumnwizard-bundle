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

namespace Richardhj\ContaoFerienpassBundle\Test\EventListener\Notification;


use ContaoCommunityAlliance\Contao\Events\Cron\CronEvents;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Richardhj\ContaoFerienpassBundle\DependencyInjection\RichardhjContaoFerienpassExtension;
use Richardhj\ContaoFerienpassBundle\EventListener\Notification\TriggerCronRemindersListener;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class TriggerCronRemindersListenerTest extends TestCase
{

    /**
     * Test that the event listener is registered.
     *
     * @return void
     * @throws \Exception
     */
    public function testListenerIsRegistered(): void
    {
        /** @var ContainerBuilder|PHPUnit_Framework_MockObject_MockObject $container */
        $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();

        $container
            ->expects($this->exactly(5))
            ->method('setDefinition')
            ->withConsecutive(
                [
                    $this->anything(),
                    $this->anything(),
                ],
                [
                    'richardhj.ferienpass.listener.notification.cron_reminders',
                    $this->callback(
                        function ($value) {
                            /** @var Definition $value */
                            $this->assertInstanceOf(Definition::class, $value);
                            $this->assertEquals(TriggerCronRemindersListener::class, $value->getClass());
                            $this->assertCount(1, $value->getTag('kernel.event_listener'));
                            $this->assertEventListener(
                                $value,
                                CronEvents::HOURLY
                            );

                            return true;
                        }
                    )
                ]
            );

        $extension = new RichardhjContaoFerienpassExtension();
        $extension->load([], $container);
    }

    /**
     * Assert that a definition is registered as event listener.
     *
     * @param Definition $definition The definition.
     * @param string     $eventName  The event name.
     * @param string     $methodName The method name.
     *
     * @return void
     */
    private function assertEventListener(Definition $definition, $eventName, $methodName='handle'): void
    {
        $this->assertCount(1, $definition->getTag('kernel.event_listener'));
        $this->assertArrayHasKey(0, $definition->getTag('kernel.event_listener'));
        $this->assertArrayHasKey('event', $definition->getTag('kernel.event_listener')[0]);
        $this->assertArrayHasKey('method', $definition->getTag('kernel.event_listener')[0]);

        $this->assertEquals($eventName, $definition->getTag('kernel.event_listener')[0]['event']);
        $this->assertEquals($methodName, $definition->getTag('kernel.event_listener')[0]['method']);
    }
}
