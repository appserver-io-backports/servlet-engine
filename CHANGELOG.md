# Version 0.7.5

## Bugfixes

* None

## Features

* Let RequestHandlerManager handle RequestHandler instances dynamically
* Throw RequestHandlerTimeoutException when we can't handle request within defined timeout

# Version 0.7.4

## Bugfixes

* None

## Features

* Add shutdown functionality for request handlers
* Handle a maximum of 50 request before shutdown request handler
* Integrate RequestHandlerManager class to asynchronously restart request handlers that has been shutdown

# Version 0.7.3

## Bugfixes

* Resolve PHPMD warnings and errors
* Bugfix for endless loop in ServletEngine::process() with concurrent requests

## Features

* None

# Version 0.7.2

## Bugfixes

* None

## Features

* Refactoring ANT PHPUnit execution process
* Composer integration by optimizing folder structure (move bootstrap.php + phpunit.xml.dist => phpunit.xml)
* Switch to new appserver-io/build build- and deployment environment

# Version 0.7.1

## Bugfixes

* None

## Features

* Add CHANGELOG.md
* Set composer dependency for techdivision/appserver to >=0.8