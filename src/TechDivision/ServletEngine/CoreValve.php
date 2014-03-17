<?php

/**
 * TechDivision\ServletEngine\CoreValve
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

use TechDivision\Http\HttpProtocol;
use \TechDivision\Servlet\ServletRequest;
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
class CoreValve
{
    
    /**
     * Processes this valve.
     *
     * @param \TechDivision\Servlet\ServletRequest  $servletRequest  The request instance
     * @param \TechDivision\Servlet\ServletResponse $servletResponse The response instance
     */
    public function invoke(HttpServletRequest $servletRequest, HttpServletResponse $servletResponse)
    {
        $this->locate($servletRequest);
    }
    
    /**
     * Tries to find an application that matches the passed request.
     * 
     * @param \TechDivision\Servlet\ServletRequest $servletRequest The request instance to locate the application for
     * 
     * @return array The application info that matches the request
     * @throws \TechDivision\ServletEngine\BadRequestException Is thrown if no application matches the request
     */
    protected function locate(ServletRequest $servletRequest)
    {

        // explode host and port from the host header
        list ($host, $port) = explode(':', $servletRequest->getHeader(HttpProtocol::HEADER_HOST));
        
        // prepare the URI to be matched
        $url =  $host . $servletRequest->getUri();
        
        // load the available applications from the request
        $applications = $servletRequest->getContext()->getApplications();
        
        // try to find the application by match it one of the prepared patterns
        foreach ($applications as $pattern => $application) {
        
            // try to match a registered application with the passed request
            if (preg_match($pattern, $url) === 1) {
                
                // prepare and set the applications context path
                $servletRequest->setContextPath($contextPath = '/' . $application->getName());

                // prepare the path information depending if we're in a vhost or not
                if ($application->isVhostOf($host) === false) {
                    $servletRequest->setServletPath(str_replace($contextPath, '', $servletRequest->getServletPath()));
                }
                
                return;
            }
        }
        
        // if not throw a bad request exception
        throw new BadRequestException(
            sprintf('Can\'t find application for URI %s', $servletRequest->getUri())
        );
    }
}