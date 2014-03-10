<?php

/**
 * TechDivision\ServletEngine\ServletLocator
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
 * @author    Markus Stockbauer <ms@techdivision.com>
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */

namespace TechDivision\ServletEngine;

use TechDivision\Servlet\ServletContext;
use TechDivision\Servlet\ServletRequest;
use TechDivision\Servlet\ServletResponse;

/**
 * The servlet resource locator implementation.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Markus Stockbauer <ms@techdivision.com>
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class ServletLocator implements ResourceLocator
{

    /**
     * The servlet manager instance.
     *
     * @var \TechDivision\Servlet\ServletContext
     */
    protected $servletManager;
    
    /**
     * The array with the servlet mappings.
     * 
     * @var array
     */
    protected $servletMappings;

    /**
     * Initializes the locator with the actual servlet manager instance.
     *
     * @param \TechDivision\Servlet\ServletContext $servletManager The servlet manager instance
     *
     * @return void
     */
    public function __construct(ServletContext $servletManager)
    {
        
        // initialize the servlet manager
        $this->servletManager = $servletManager;

        // retrieve the registered servlets
        $this->servletMappings = $this->getServletContext()->getServletMappings();
    }

    /**
     * Returns the servlet manager instance to use.
     *
     * @return \TechDivision\Servlet\ServletContext The servlet manager instance to use
     */
    public function getServletContext()
    {
        return $this->servletManager;
    }

    /**
     * Returns the array with the servlet mappings.
     *
     * @return array The array with the servlet mappings
     */
    public function getServletMappings()
    {
        return $this->servletMappings;
    }

    /**
     * Returns the actual application instance.
     *
     * @return \TechDivision\ServletEngine\Application The application instance
     */
    public function getApplication()
    {
        return $this->getServletContext()->getApplication();
    }

    /**
     * Tries to locate a servlet for the passed request instance.
     *
     * @param \TechDivision\ServletContainer\Http\ServletRequest $servletRequest The request instance to return the servlet for
     *
     * @return \TechDivision\Servlet\Servlet The requested servlet
     * @throws \TechDivision\ServletEngine\ServletNotFoundException Is thrown if no servlet can be found for the passed request
     * @see \TechDivision\ServletEngine\ResourceLocatorInterface::locate()
     */
    public function locate(ServletRequest $servletRequest)
    {
        
        // load the path to the (almost virtual servlet)
        $servletPath = $servletRequest->getServletPath();
        
        // iterate over all servlets and return the matching one
        foreach ($this->getServletMappings() as $urlPattern => $servletName) {
            if (fnmatch($urlPattern, $servletPath)) {
                return $this->getServletContext()->getServlet($servletName);
            }
        }
        
        // throw an exception if no servlet matches the servlet path
        throw new ServletNotFoundException(
            sprintf("Can't find servlet for requested path %s", $servletPath)
        );
    }
}
