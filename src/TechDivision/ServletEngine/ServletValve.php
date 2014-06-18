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

        // load the session manager and the servlet context
        $sessionManager = $servletRequest->getContext()->getSessionManager();
        $servletContext = $servletRequest->getContext()->getServletContext();

        // if we've session parameters defined in our servlet context
        if ($servletContext->hasSessionParameters()) { // we want to merge the session settings from the servlet context into our session manager
            $sessionManager->getSettings()->mergeServletContext($servletContext);
        }

        // after that, we can service the request
        $servletRequest->getContext()->locate($servletRequest)->service($servletRequest, $servletResponse);
    }
}
