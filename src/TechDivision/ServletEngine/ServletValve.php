<?php

/**
 * TechDivision\ServletEngine\ServletValve
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
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */

namespace TechDivision\ServletEngine;

use \TechDivision\Servlet\ServletSession;
use \TechDivision\Servlet\Http\HttpServletRequest;
use \TechDivision\Servlet\Http\HttpServletResponse;

/**
 * Valve implementation that will be executed by the servlet engine to handle
 * an incoming Http servlet request.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class ServletValve
{

    /**
     * Load the actual context instance, the servlet and handle the request.
     *
     * @param \TechDivision\Servlet\ServletRequest  $servletRequest  The request instance
     * @param \TechDivision\Servlet\ServletResponse $servletResponse The response instance
     *
     * @return void
     */
    public function invoke(HttpServletRequest $servletRequest, HttpServletResponse $servletResponse)
    {

        // load the application context
        $context = $servletRequest->getContext();

        // we need a synchronized context here
        $context->synchronized(function ($self, $request, $response) {

            // pass servlet requset/response to application
            $self->servletRequest = $request;
            $self->servletResponse = $response;

            // set the flag that a new request has to be handled
            $self->handleRequest = true;

            // notify the application because it waits
            $self->notify();

            // wait until the application sends us a notification with notify()
            $self->wait();

            // copy the headers back to the local request
            foreach ($self->servletResponse->getHeaders() as $header => $value) {
                $response->addHeader($header, $value);
            }

            // copy the cookies back to the local request
            foreach ($self->servletResponse->getCookies() as $cookie => $value) {
                $response->addCookie($cookie, $value);
            }

            // copy the body stream
            $response->appendBodyStream($self->bodyStream);

        }, $context, $servletRequest, $servletResponse);
    }
}
