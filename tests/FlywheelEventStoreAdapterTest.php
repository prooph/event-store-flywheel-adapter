<?php

/*
 * This file is part of the prooph/event-store-flywheel-adapter.
 *
 * (c) 2016 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ProophTest\EventStore\Adapter\Flywheel;

use PHPUnit_Framework_TestCase as TestCase;
use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\EventStore\Adapter\Flywheel\FlywheelEventStoreAdapter;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;

class FlywheelEventStoreAdapterTest extends TestCase
{
    /**
     * @var string
     */
    private $rootDir;

    /**
     * @var FlywheelEventStoreAdapter
     */
    private $adapter;

    protected function setUp()
    {
        $this->rootDir = sys_get_temp_dir().'/FlywheelEventStoreAdapterTest_'.rand();

        if (!is_dir($this->rootDir) && !@mkdir($this->rootDir)) {
            throw new \RuntimeException('Unable to create the temporary root directory');
        }

        $this->adapter = new FlywheelEventStoreAdapter(
            $this->rootDir,
            new FQCNMessageFactory(),
            new NoOpMessageConverter()
        );
    }

    protected function tearDown()
    {
        if (!$this->removePath($this->rootDir)) {
            throw new \RuntimeException('Unable to remove the temporary root directory');
        }
    }

    private function removePath($path)
    {
        if (is_file($path)) {
            return @unlink($path);
        } elseif (!is_dir($path)) {
            return false;
        }

        foreach (glob($path.'/*') as $item) {
            $this->removePath($item);
        }

        return @rmdir($path);
    }

    /**
     * @test
     */
    public function it_creates_and_load_a_stream()
    {
        // Create stream
        $stream = $this->createStream();
        $this->adapter->create($stream);

        // Load stream
        $stream = $this->adapter->load(new StreamName('user_stream'));
        $events = $stream->streamEvents();

        // Assertion on the stream
        $this->assertEquals('user_stream', $stream->streamName()->toString());
        $this->assertCount(1, $events);

        // Assertion on the event
        $event = $events[0];
        $this->assertEquals($event->uuid()->toString(), $event->uuid()->toString());
        $this->assertEquals(UserCreated::class, $event->messageName());
        $this->assertEquals('Max Mustermann', $event->payload()['name']);
        $this->assertEquals('contact@prooph.de', $event->payload()['email']);
        $this->assertEquals(1, $event->version());
        $this->assertEquals(['tag' => 'person'], $event->metadata());
    }

    /**
     * @test
     */
    public function it_appends_events_to_a_stream()
    {
        // Create stream
        $stream = $this->createStream();
        $this->adapter->create($stream);

        // Append a new event to the stream
        $newStream = UsernameChanged::with(['name' => 'John Doe'], 2);
        $newStream = $newStream->withAddedMetadata('tag', 'person');
        $this->adapter->appendTo(new StreamName('user_stream'), new \ArrayIterator([$newStream]));

        // Load events
        $events = $this->adapter->loadEvents(new StreamName('user_stream'), ['tag' => 'person']);
        $this->assertCount(2, $events);

        // Assertion on the last events
        $lastEvent = $events[1];
        $messageConverter = new NoOpMessageConverter();
        $this->assertInstanceOf(UsernameChanged::class, $lastEvent);
        $this->assertEquals(
            $messageConverter->convertToArray($newStream),
            $messageConverter->convertToArray($lastEvent),
            'Loaded event equals the inserted event'
        );
    }

    /**
     * @test
     */
    public function it_loads_events_from_min_version_on()
    {
        // Create stream
        $stream = $this->createStream();
        $this->adapter->create($stream);

        // Append new events
        $newEvents = [
            UsernameChanged::with(['name' => 'John Doe'], 2)->withAddedMetadata('tag', 'person'),
            UsernameChanged::with(['name' => 'Jane Doe'], 3)->withAddedMetadata('tag', 'person'),
        ];
        $this->adapter->appendTo(new StreamName('user_stream'), new \ArrayIterator($newEvents));

        // Load all events from version 2
        $events = $this->adapter->loadEvents(new StreamName('user_stream'), [], 2);

        // Assertion on stream events
        $this->assertCount(2, $events);
        $this->assertEquals($newEvents[0]->toArray(), $events[0]->toArray());
        $this->assertEquals($newEvents[1]->toArray(), $events[1]->toArray());
    }

    /**
     * @test
     */
    public function it_replays()
    {
        // Create stream
        $stream = $this->createStream();
        $this->adapter->create($stream);

        // Append new event
        $newEvents = [
            UsernameChanged::with(['name' => 'John Doe'], 2)->withAddedMetadata('tag', 'person'),
            UsernameChanged::with(['name' => 'Jane Doe'], 3)->withAddedMetadata('tag', 'person'),
        ];
        $this->adapter->appendTo(new StreamName('user_stream'), new \ArrayIterator($newEvents));

        // Load events with replay
        $events = $this->adapter->replay(new StreamName('user_stream'), null, ['tag' => 'person']);

        // Assertion on stream events
        $this->assertCount(3, $events);
        $this->assertEquals(UserCreated::class, $events[0]->messageName());
        $this->assertEquals(1, $events[0]->version());
        $this->assertEquals($newEvents[0]->toArray(), $events[1]->toArray());
        $this->assertEquals($newEvents[1]->toArray(), $events[2]->toArray());
    }

    /**
     * @test
     */
    public function it_replays_from_specific_date()
    {
        // Create stream
        $stream = $this->createStream();
        $this->adapter->create($stream);

        $since = new \DateTime('now', new \DateTimeZone('UTC'));

        // Append new event
        $newEvents = [
            UsernameChanged::with(['name' => 'John Doe'], 2)->withAddedMetadata('tag', 'person'),
            UsernameChanged::with(['name' => 'Jane Doe'], 3)->withAddedMetadata('tag', 'person'),
        ];
        $this->adapter->appendTo(new StreamName('user_stream'), new \ArrayIterator($newEvents));

        // Load events with replay
        $streamEvents = $this->adapter->replay(new StreamName('user_stream'), $since, ['tag' => 'person']);

        // Assertion on stream events
        $this->assertCount(2, $streamEvents);
        $this->assertEquals($newEvents[0]->toArray(), $streamEvents[0]->toArray());
        $this->assertEquals($newEvents[1]->toArray(), $streamEvents[1]->toArray());
    }

    /**
     * @test
     */
    public function it_replays_events_of_two_aggregates_in_a_single_stream_in_correct_order()
    {
        // Create stream
        $stream = $this->createStream();
        $this->adapter->create($stream);

        // Append new event
        $newEvent1 = UsernameChanged::with(['name' => 'John Doe'], 2)->withAddedMetadata('tag', 'person');
        $this->adapter->appendTo(new StreamName('user_stream'), new \ArrayIterator([$newEvent1]));

        // Append new event for different aggragate
        $newEvent2 = UserCreated::with(['name' => 'Jane Doe', 'email' => 'jane@acme.com'], 1)->withAddedMetadata('tag', 'person');
        $this->adapter->appendTo(new StreamName('user_stream'), new \ArrayIterator([$newEvent2]));

        // Load events with replay
        $streamEvents = $this->adapter->replay(new StreamName('user_stream'), null, ['tag' => 'person']);

        $replayedPayloads = [];
        foreach ($streamEvents as $event) {
            $replayedPayloads[] = $event->payload();
        }

        $expectedPayloads = [
            ['name' => 'Max Mustermann', 'email' => 'contact@prooph.de'],
            ['name' => 'John Doe'],
            ['name' => 'Jane Doe', 'email' => 'jane@acme.com'],
        ];

        $this->assertEquals($expectedPayloads, $replayedPayloads);
    }

    /**
     * @return Stream
     */
    private function createStream()
    {
        $streamEvent = UserCreated::withPayloadAndSpecifiedCreatedAt(
            ['name' => 'Max Mustermann', 'email' => 'contact@prooph.de'],
            1,
            new \DateTimeImmutable('30 seconds ago')
        );

        $streamEvent = $streamEvent->withAddedMetadata('tag', 'person');

        return new Stream(
            new StreamName('user_stream'),
            new \ArrayIterator([$streamEvent])
        );
    }
}
