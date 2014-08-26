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

use TechDivision\Server\Dictionaries\ServerVars;
use TechDivision\Servlet\Http\HttpServletRequest;
use TechDivision\Servlet\Http\HttpServletResponse;
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
    }

    /**
     * Inject the actual servlet request.
     *
     * @param \TechDivision\Serlvet\Http\HttpServletRequest $servletRequest The actual request instance
     *
     * @return void
     */
    public function injectRequest(HttpServletRequest $servletRequest)
    {
        $this->servletRequest = $servletRequest;
    }

    /**
     * Inject the actual servlet response.
     *
     * @param \TechDivision\Serlvet\Http\HttpServletResponse $servletResponse The actual response instance
     *
     * @return void
     */
    public function injectResponse(HttpServletResponse $servletResponse)
    {
        $this->servletResponse = $servletResponse;
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

        try {

            // reset request/response instance
            $application = $this->application;

            // register the class loader again, because each thread has its own context
            $application->registerClassLoaders();

            // synchronize the servlet request/response
            $servletRequest = $this->servletRequest;
            $servletResponse = $this->servletResponse;

            // prepare and set the applications context path
            $servletRequest->setContextPath($contextPath = '/' . $application->getName());

            // prepare the path information depending if we're in a vhost or not
            if ($application->isVhostOf($servletRequest->getServerVar(ServerVars::SERVER_NAME)) === false) {
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
            error_log($e->__toString());
            $servletResponse->appendBodyStream($e->__toString());
            $servletResponse->setStatusCode(500);
        }
    }

    /**
     * Does shutdown logic for request handler if something went wrong and produces
     * a fatal error for example.
     *
     * @return void
     */
    public function shutdown()
    {

        // check if there was a fatal error caused shutdown
        $lastError = error_get_last();
        if ($lastError['type'] === E_ERROR || $lastError['type'] === E_USER_ERROR) {
            error_log($lastError['message']);
        }
    }
}
