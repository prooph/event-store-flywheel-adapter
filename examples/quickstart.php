<?php

/*
 * This file is part of the prooph/event-store-flywheel-adapter.
 *
 * (c) 2016 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__.'/../vendor/autoload.php';

use Prooph\Common\Event\ProophActionEventEmitter;
use Prooph\Common\Messaging\FQCNMessageFactory;
use Prooph\Common\Messaging\NoOpMessageConverter;
use Prooph\EventStore\Adapter\Flywheel\FlywheelEventStoreAdapter;
use Prooph\EventStore\EventStore;
use Prooph\EventStore\Stream\Stream;
use Prooph\EventStore\Stream\StreamName;
use ProophTest\EventStore\Mock\UserCreated;
use ProophTest\EventStore\Mock\UsernameChanged;

$rootDir = __DIR__.'/event_store';
$adapter = new FlywheelEventStoreAdapter(
    $rootDir,
    new FQCNMessageFactory(),
    new NoOpMessageConverter()
);
$actionEmitter = new ProophActionEventEmitter();
$eventStore = new EventStore($adapter, $actionEmitter);

$streamName = new StreamName('event_stream');
$stream = new Stream($streamName, new \ArrayIterator([]));

//
// Persist some events in the event store
//
$eventStore->beginTransaction();
$eventStore->create($stream);
$eventStore->appendTo($streamName, new \ArrayIterator([
    UserCreated::with(['name' => 'Max Mustermann'], 1)->withAddedMetadata('tag', 'person'),
    UsernameChanged::with(['name' => 'John Doe'], 2)->withAddedMetadata('tag', 'person'),
]));
$eventStore->commit();

//
// Load all the stored events
//
$persistedEventStream = $eventStore->load($streamName);
foreach ($persistedEventStream->streamEvents() as $event) {
    echo $event->payload()['name'].PHP_EOL;
}
