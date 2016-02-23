# Flywheel Adapter for ProophEventStore

[![Build Status](https://travis-ci.org/prooph/event-store-flywheel-adapter.svg?branch=master)](https://travis-ci.org/prooph/event-store-flywheel-adapter)
[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/prooph/improoph)

Use [Prooph Event Store](https://github.com/prooph/event-store)
with [Flywheel](https://github.com/jamesmoss/flywheel).

## Overview

**Flywheel** is a serverless document database which only uses flat files on
your local filesystem to store the data.
All the events will be stored and loaded from a choosen directory.
This is well suited when you bootstrap an application and you don't need a
real database server right away.
It can also be a good candidate for writing functionnal tests.

But of course you **must not run it in production** since it is not designed
to handle a huge amount of events and doesn't manage transactions.

## Installation

You can install this package via Composer:

```console
composer require prooph/event-store-flywheel-adapter
```

## Usage

See the [quickstart example](./examples/quickstart.php).

It creates some events and store them in JSON files in the `quickstart/event_store` directory.
Here is an example of a created JSON file:

```json
{
    "event_id": "4e5bba37-e2bb-46d3-9988-e2ec6b02e664",
    "version": 1,
    "event_name": "ProophTest\\EventStore\\Mock\\UserCreated",
    "payload": {
        "name": "Max Mustermann"
    },
    "metadata": {
        "tag": "person"
    },
    "created_at": "2016-02-25T13:28:54.365200"
}
```

## Support

- File issues at [https://github.com/prooph/event-store/issues](https://github.com/prooph/event-store/issues).
- Say hello in the [prooph gitter](https://gitter.im/prooph/improoph) chat.

## Contribute

Please **feel free to fork**, extend existing and send a pull request with your changes!
To establish a consistent code quality, please provide **unit tests** for all your changes.
You are also encouraged to use the `composer lint` command to validate the **coding standards**.

## License

Released under the [New BSD License](./LICENSE).
