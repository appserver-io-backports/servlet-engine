<?php

/**
 * TechDivision\ServletEngine\RequestHandler
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category  Appserver
 * @package   TechDivision_ServletModule
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */

namespace TechDivision\ServletEngine;

use \TechDivision\Http\HttpResponseStates;
use TechDivision\Application\Interfaces\ApplicationInterface;

/**
 * This is a request handler that is necessary to process each request of an
 * application in a separate context.
 *
 * @category  Appserver
 * @package   TechDivision_ServletModule
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class RequestHandler extends \Thread
{

    /**
     * The application instance we're processing requests for.
     *
     * @return \TechDivision\ApplicationServer\Interfaces\ApplicationInterface
     */
    protected $application;

    /**
     * The valves we're processing each request with.
     *
     * @return \TechDivision\Storage\GenericStackable
     */
    protected $valves;

    /**
     * The actual request instance we have to process.
     *
     * @return \TechDivision\Servlet\Http\HttpServletRequest
     */
    protected $servletRequest;

    /**
     * The actual response instance we have to process.
     *
     * @return \TechDivision\Servlet\Http\HttpServletResponse
     */
    protected $servletResponse;

    /**
     * Flag to allow/disallow request handling.
     *
     * @return boolean
     */
    protected $handleRequest;

    /**
     * Initializes the request handler with the application and the
     * valves to be processed
     *
     * @param \TechDivision\ApplicationServer\Interfaces\ApplicationInterface $application The application instance
     * @param \TechDivision\Storage\GenericStackable                          $valves      The valves to process
     */
    public function __construct(ApplicationInterface $application, $valves)
    {

        // initialize the request handlers application
        $this->application = $application;
        $this->valves = $valves;

        $this->handleRequest = false;

        // start the request processing
        $this->start();
    }

    /**
     * Returns the valves we're processing each request with.
     *
     * @return \TechDivision\Storage\GenericStackable The valves
     */
    protected function getValves()
    {
        return $this->valves;
    }

    /**
     * The main method that handles the thread in a separate context.
     *
     * @return void
     */
    public function run()
    {

        while (true) {

            // synchronize the response data
            $this->synchronized(function ($self) {

                // wait until we've to handle a new request
                $self->wait();

                // check if we've to handle a request
                if ($self->handleRequest) {

                    try {

                        // reset request/response instance
                        $application = $self->application;

                        // register the class loader again, because each thread has its own context
                        $application->registerClassLoaders();

                        // synchronize the servlet request/response
                        $servletRequest = $self->servletRequest;
                        $servletResponse = $self->servletResponse;

                        // prepare and set the applications context path
                        $servletRequest->setContextPath($contextPath = '/' . $application->getName());

                        // prepare the path information depending if we're in a vhost or not
                        if ($application->isVhostOf($host) === false) {
                            $servletRequest->setServletPath(str_replace($contextPath, '', $servletRequest->getServletPath()));
                        }

                        // inject the found application into the servlet request
                        $servletRequest->injectContext($application);

                        // process the valves
                        foreach ($this->getValves() as $valve) {
                            $valve->invoke($servletRequest, $servletResponse);
                            if ($servletRequest->isDispatched() === true) {
                                break;
                            }
                        }

                    } catch (\Exception $e) {
                        $servletResponse->appendBodyStream($e->__toString());
                        $servletResponse->setStatusCode(500);
                    }

                    // set the request state to dispatched
                    $servletResponse->setState(HttpResponseStates::DISPATCH);

                    // reset the flag
                    $self->handleRequest = false;
                }

            }, $this);
        }
    }
}
