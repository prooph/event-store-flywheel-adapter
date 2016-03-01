<?php

/*
 * This file is part of the prooph/event-store-flywheel-adapter.
 *
 * (c) 2016 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\EventStore\Adapter\Flywheel\Container;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_TestCase as TestCase;
use Prooph\Common\Messaging\MessageConverter;
use Prooph\Common\Messaging\MessageFactory;
use Prooph\EventStore\Adapter\Flywheel\Container\FlywheelEventStoreAdapterFactory;
use Prooph\EventStore\Adapter\Flywheel\FlywheelEventStoreAdapter;

class FlywheelEventStorageAdapterFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_create_adapter_using_configured_directory()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $config['prooph']['event_store']['adapter']['options']['dir'] = __DIR__;

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);
        $container->has(MessageFactory::class)->willReturn(false);
        $container->has(MessageConverter::class)->willReturn(false);

        $factory = new FlywheelEventStoreAdapterFactory();

        $adapter = $factory($container->reveal());

        $this->assertInstanceOf(FlywheelEventStoreAdapter::class, $adapter);
    }

    /**
     * @test
     */
    public function it_injects_helpers_from_container_if_available()
    {
        $messageFactory = $this->prophesize(MessageFactory::class);
        $messageConverter = $this->prophesize(MessageConverter::class);

        $container = $this->prophesize(ContainerInterface::class);

        $config['prooph']['event_store']['adapter']['options']['dir'] = __DIR__;

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);
        $container->has(MessageFactory::class)->willReturn(true);
        $container->get(MessageFactory::class)->willReturn($messageFactory);
        $container->has(MessageConverter::class)->willReturn(true);
        $container->get(MessageConverter::class)->willReturn($messageConverter);

        $factory = new FlywheelEventStoreAdapterFactory();

        $adapter = $factory($container->reveal());

        $this->assertInstanceOf(FlywheelEventStoreAdapter::class, $adapter);
    }

    /**
     * @test
     * @expectedException \Prooph\EventStore\Exception\ConfigurationException
     */
    public function it_throws_exception_if_adaptaer_directory_options_not_found()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $config['prooph']['event_store']['adapter']['options']['dir'] = 'not-found-dir';

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);
        $container->has(MessageFactory::class)->willReturn(false);
        $container->has(MessageConverter::class)->willReturn(false);

        $factory = new FlywheelEventStoreAdapterFactory();

        $factory($container->reveal());
    }

    /**
     * @test
     * @expectedException \Interop\Config\Exception\MandatoryOptionNotFoundException
     */
    public function it_throws_exception_if_adapter_options_are_not_available()
    {
        $container = $this->prophesize(ContainerInterface::class);

        $config['prooph']['event_store']['adapter']['options'] = [];

        $container->has('config')->willReturn(true);
        $container->get('config')->willReturn($config);
        $container->has(MessageFactory::class)->willReturn(false);
        $container->has(MessageConverter::class)->willReturn(false);

        $factory = new FlywheelEventStoreAdapterFactory();

        $factory($container->reveal());
    }
}
