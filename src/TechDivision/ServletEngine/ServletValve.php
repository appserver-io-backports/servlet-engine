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

use \TechDivision\ServletEngine\Valve;
use \TechDivision\Servlet\Http\HttpServletRequest;
use \TechDivision\Servlet\Http\HttpServletResponse;

/**
 * Valve implementation that will be executed by the servlet engine to handle
 * an incoming HTTP servlet request.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class ServletValve implements Valve
{

    /**
     * Processes the request by invoking the request handler that executes the servlet
     * in a protected context.
     *
     * @param \TechDivision\Servlet\ServletRequest  $servletRequest  The request instance
     * @param \TechDivision\Servlet\ServletResponse $servletResponse The response instance
     *
     * @return void
     */
    public function invoke(HttpServletRequest $servletRequest, HttpServletResponse $servletResponse)
    {

        // load the servlet context
        $servletContext = $servletRequest->getContext()->getServletContext();

        // locate and service the servlet
        $servletContext->locate($servletRequest)->service($servletRequest, $servletResponse);

        // dispatch this request, because we have finished processing it
        $servletRequest->setDispatched(true);
    }
}
