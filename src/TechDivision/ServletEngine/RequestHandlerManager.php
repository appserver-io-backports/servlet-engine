<?php

/**
 * TechDivision\ServletEngine\RequestHandlerManager
 *
 * PHP version 5
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2013 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */

namespace TechDivision\ServletEngine;

use TechDivision\Storage\GenericStackable;

/**
 * Manager that handles the creation of request handlers.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2013 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class RequestHandlerManager extends \Thread
{

    /**
     * The request handlers we have to manage.
     *
     * @var \TechDivision\Storage\GenericStackable
     */
    protected $requestHandlers;

    /**
     * The valves the request handler has to process for each request.
     *
     * @var \TechDivision\Storage\GenericStackable
     */
    protected $valves;

    /**
     * The applications that has to be bound to a request handler.
     *
     * @var \TechDivision\Storage\GenericStackable
     */
    protected $applications;

    /**
     * Initializes the request handler manager instance.
     *
     * @param \TechDivision\Storage\GenericStackable $requestHandlers The request handlers we have to manage
     * @param \TechDivision\Storage\GenericStackable $applications    The valves the request handler has to process for each request
     * @param \TechDivision\Storage\GenericStackable $valves          The applications that has to be bound to a request handler
     */
    public function __construct($requestHandlers, $applications, $valves)
    {

        // set the passed variables
        $this->requestHandlers = $requestHandlers;
        $this->applications = $applications;
        $this->valves = $valves;

        // autostart the manager
        $this->start(PTHREADS_INHERIT_ALL | PTHREADS_ALLOW_HEADERS);
    }

    /**
     * Starts the request handler manager-
     *
     * @return void
     */
    public function run()
    {

        // create a local copy of the valves
        $valves = $this->valves;
        $applications = $this->applications;
        $requestHandlers = $this->requestHandlers;

        while (true) { // we run forever and make sure that enough request handlers are available

            // wait until we'll be notfied to check if we've to create new request handlers
            $this->wait();

            // we want to prepare an request for each application and each worker
            foreach ($applications as $applicationName => $application) {

                // shutdown the outdated request handlers
                foreach ($requestHandlers[$applicationName] as $threadId => $requestHandler) {

                    // check if a handler should be restarted
                    if ($requestHandlers[$applicationName][$threadId]->shouldRestart()) {

                        // remove the handler
                        unset($requestHandlers[$applicationName][$threadId]);

                        // add a new one
                        $requestHandler = new RequestHandler($application, $valves);
                        $requestHandlers[$applicationName][$requestHandler->getThreadId()] = $requestHandler;
                    }
                }
            }
        }
    }
}
