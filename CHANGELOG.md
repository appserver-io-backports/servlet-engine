# Version 0.8.6

## Bugfixes

* None

## Features

* Updating for pthreads-1.0.2

# Version 0.8.5

## Bugfixes

* None

## Features

* Use application context to lookup manager instances
* Update constant IDENTIFIER to use short class name instead of fully qualified one

# Version 0.8.4

## Bugfixes

* None

## Features

* Add DI functionality for servlets, Issue #4 in (techdivision/TechDivision_ApplicationServer)

# Version 0.8.3

## Bugfixes

* None

## Features

* Add Request::__cleanup() method to unset application context when worker threads have been shutdown
* Add magic RequestHandler::__shutdown() method that'll be invoked when a thread will be shutdown

# Version 0.8.2

## Bugfixes

* Make SessionFactory::nextFromPool() + SessionFactory::removeBySession() methods protected

## Features

* None

# Version 0.8.1

## Bugfixes

* None

## Features

* Add dependency to new appserver-io/logger library
* Integration of monitoring/profiling functionality
* Revert integration to initialize manager instances with thread based factories

# Version 0.8.0

## Bugfixes

* None

## Features

* Integration to initialize manager instances with thread based factories

# Version 0.7.12

## Bugfixes

* Inject all Stackable instances instead of initialize them in ServletManager::__construct + StandardSessionManager::__construct => pthreads 2.x compatibility

## Features

* None

# Version 0.7.11

## Bugfixes

* Add synchronized() method around all wait()/notify() calls => pthreads 2.x compatibility

## Features

* None

# Version 0.7.10

## Bugfixes

* None

## Features

* Switch to new ClassLoader + ManagerInterface
* Add configuration parameters to manager configuration

# Version 0.7.9

## Bugfixes

* None

## Features

* Add shutdown handler method to RequestHandler class

# Version 0.7.8

## Bugfixes

* None

## Features

* Add static (no dynamic) request handler and servlet engine version

# Version 0.7.7

## Bugfixes

* Make TTL for request handler random between 10 and 50 seconds

## Features

* Move HttpSessionWrapper implementation from TechDivision_ServletEngine to this library

# Version 0.7.6

## Bugfixes

* Bugfix invalid counter for waiting RequestHandler instances

## Features

* Add ttl with a default value of 10 seconds to RequestHandler

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