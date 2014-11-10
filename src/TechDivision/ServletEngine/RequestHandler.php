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

use AppserverIo\Logger\LoggerUtils;
use TechDivision\Storage\GenericStackable;
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
     * Injects the valves to be processed.
     *
     * @param \TechDivision\Storage\GenericStackable $valves The valves to process
     *
     * @return void
     */
    public function injectValves(GenericStackable $valves)
    {
        $this->valves = $valves;
    }

    /**
     * Injects the application of the request to be handled
     *
     * @param \TechDivision\ApplicationServer\Interfaces\ApplicationInterface $application The application instance
     *
     * @return void
     */
    public function injectApplication(ApplicationInterface $application)
    {
        $this->application = $application;
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
     * The main method that handles the thread in a separate context.
     *
     * @return void
     */
    public function run()
    {

        try {

            // register shutdown handler
            register_shutdown_function(array(&$this, "shutdown"));

            // reset request/response instance
            $application = $this->application;

            // register class loaders
            $application->registerClassLoaders();

            // synchronize the valves, servlet request/response
            $valves = $this->valves;
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
            foreach ($valves as $valve) {
                $valve->invoke($servletRequest, $servletResponse);
                if ($servletRequest->isDispatched() === true) {
                    break;
                }
            }

            // profile the request if the profile logger is available
            if ($profileLogger = $application->getInitialContext()->getLogger(LoggerUtils::PROFILE)) {
                $profileLogger->appendThreadContext('request-handler');
                $profileLogger->info($servletRequest->getUri());
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

            // synchronize the servlet response
            $servletResponse = $this->servletResponse;

            // set the status code and append the error message to the body
            $servletResponse->setStatusCode(500);
            $servletResponse->appendBodyStream($lastError['message']);
        }
    }
}
