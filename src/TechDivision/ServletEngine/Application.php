<?php

/**
 * TechDivision\ServletEngine\Application
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


use TechDivision\Servlet\Servlet;
use TechDivision\Servlet\ServletContext;
use TechDivision\Servlet\ServletRequest;
use TechDivision\ApplicationServer\Vhost;
use TechDivision\ApplicationServer\Configuration;
use TechDivision\ApplicationServer\AbstractApplication;

/**
 * The application instance holds all information about the deployed application
 * and provides a reference to the servlet manager and the initial context.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class Application extends AbstractApplication
{

    /**
     * The servlet manager.
     *
     * @var \TechDivision\Servlet\ServletContext
     */
    protected $servletContext;

    /**
     * The servlet locator.
     *
     * @var \TechDivision\ServletEngine\ServletLocator
     */
    protected $servletLocator;

    /**
     * Array with available VHost configurations.
     * 
     * @array
     */
    protected $vhosts = array();
    
    /**
     * The servlet cache that maps a request to the servlet that has to handle it.
     * 
     * @var array
     */
    protected $servletCache = array();

    /**
     * Has been automatically invoked by the container after the application
     * instance has been created.
     *
     * @return \TechDivision\ServletEngine\Application The connected application
     */
    public function connect()
    {

        // also initialize the vhost configuration
        parent::connect();

        // initialize the class loader with the additional folders
        set_include_path(get_include_path() . PATH_SEPARATOR . $this->getWebappPath());
        set_include_path(get_include_path() . PATH_SEPARATOR . $this->getWebappPath() . DIRECTORY_SEPARATOR . 'WEB-INF' . DIRECTORY_SEPARATOR . 'classes');
        set_include_path(get_include_path() . PATH_SEPARATOR . $this->getWebappPath() . DIRECTORY_SEPARATOR . 'WEB-INF' . DIRECTORY_SEPARATOR . 'lib');

        // initialize the servlet manager instance
        $servletContext = $this->newInstance('TechDivision\ServletEngine\ServletManager', array(
            $this
        ));

        // set the servlet manager
        $this->setServletContext($servletContext->initialize());
        
        // initialize the servlet locator instance
        $servletLocator = $this->newInstance('TechDivision\ServletEngine\ServletLocator', array(
            $this->getServletContext()
        ));
        
        // set the servlet locator
        $this->setServletLocator($servletLocator);

        // return the instance itself
        return $this;
    }

    /**
     * Return's the server software.
     *
     * @return string The server software
     */
    public function getServerSoftware()
    {
        return $this->getContainerNode()->getHost()->getServerSoftware();
    }

    /**
     * Return's the server admin email.
     *
     * @return string The server admin email
     */
    public function getServerAdmin()
    {
        return $this->getContainerNode()->getHost()->getServerAdmin();
    }

    /**
     * Sets the applications servlet context instance.
     *
     * @param \TechDivision\Servlet\ServletContext $servletContext The servlet context instance
     *
     * @return void
     */
    public function setServletContext(ServletContext $servletContext)
    {
        $this->servletContext = $servletContext;
    }

    /**
     * Return the servlet context instance.
     *
     * @return \TechDivision\Servlet\ServletContext The servlet context instance
     */
    public function getServletContext()
    {
        return $this->servletContext;
    }

    /**
     * Sets the applications servlet locator instance.
     *
     * @param \TechDivision\ServletEngine\ResourceLocator $servletLocator The servlet locator instance
     *
     * @return void
     */
    public function setServletLocator(ResourceLocator $servletLocator)
    {
        $this->servletLocator = $servletLocator;
    }

    /**
     * Return the servlet locator instance.
     *
     * @return \TechDivision\ServletEngine\ResourceLocator The servlet locator instance
     */
    public function getServletLocator()
    {
        return $this->servletLocator;
    }

    /**
     * Locates and returns the servlet instance that handles
     * the request passed as parameter.
     * 
     * @param \TechDivision\Servlet\ServletRequest $servletRequest The request instance
     *
     * @return \TechDivision\Servlet\Servlet The servlet instance to handle the request
     */
    public function locate(ServletRequest $servletRequest)
    {
        
        // try to locate the servlet
        $servlet = $this->getServletLocator()->locate($servletRequest);
        
        // secure the servlet if necessary
        $this->secureServlet($servlet, $servletRequest->getPathInfo());
        
        // return the servlet instance
        return $servlet;
    }

    /**
     * Check if the requested URI matches a secured url pattern and
     * secure the servlet with the configured authentication method.
     *
     * @param \TechDivision\Servlet\Servlet $servlet  A servlet instance
     * @param string                        $pathInfo The URI to resolve
     *
     * @return void
     */
    protected function secureServlet(Servlet $servlet, $pathInfo)
    {
        // iterate over all servlets and return the matching one
        foreach ($this->getServletContext()->getSecuredUrlConfigs() as $securedUrlConfig) {
            list ($urlPattern, $auth) = array_values($securedUrlConfig);
            if (fnmatch($urlPattern, $pathInfo)) {
                $servlet->injectSecuredUrlConfig($auth);
                $servlet->setAuthenticationRequired(true);
                break;
            }
        }
    }
}
