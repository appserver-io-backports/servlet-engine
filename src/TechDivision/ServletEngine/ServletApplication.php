<?php

/**
 * TechDivision\ServletEngine\ServletApplication
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
use TechDivision\Servlet\Http\HttpServletRequest;
use TechDivision\ApplicationServer\Api\ContainerService;
use TechDivision\ApplicationServer\Api\Node\AppNode;
use TechDivision\ApplicationServer\Api\Node\NodeInterface;
use TechDivision\ApplicationServer\Interfaces\ApplicationInterface;
use TechDivision\ApplicationServer\Interfaces\ContextInterface;

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
class ServletApplication implements Application, ApplicationInterface
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
     * The initial context instance.
     *
     * @var \TechDivision\ApplicationServer\Interfaces\ContextInterface
     */
    protected $initialContext;

    /**
     * The unique application name.
     *
     * @var string
     */
    protected $name;

    /**
     * Array with available virtual host configurations.
     * 
     * @var array
     */
    protected $virtualHosts = array();

    /**
     * The datasources the app might use.
     *
     * @var array
     */
    protected $datasources = array();
    
    /**
     * The directory with the web applications.
     * 
     * @var string
     */
    protected $appBase;
    
    /**
     * The servlet engines base directory.
     * 
     * @var string
     */
    protected $baseDirectory;
    
    /**
     * Injects the datasources the app might use.
     * 
     * @param array $datasources The datasources the app might use
     * 
     * @return void
     */
    public function injectDatasources(array $datasources)
    {
        $this->datasources = $datasources;
    }
    
    /**
     * Injects the directory containing the web applications.
     * 
     * @param string $appBase The directory containing the web applications
     * 
     * @return void
     */
    public function injectAppBase($appBase)
    {
        $this->appBase = $appBase;
    }
    
    /**
     * Injects the application name.
     * 
     * @param string $name The application name
     * 
     * @return void
     */
    public function injectName($name)
    {
        $this->name = $name;
    }
    
    /**
     * Injects the servlet engines base directory.
     * 
     * @param string $baseDirectory The servlet engines base directory
     * 
     * @return void
     */
    public function injectBaseDirectory($baseDirectory)
    {
        $this->baseDirectory = $baseDirectory;
    }
    
    /**
     * Injects the initial context instance.
     * 
     * @param \TechDivision\ApplicationServer\Interfaces\ContextInterface $initialContext The initial context instance
     * 
     * @return void
     */
    public function injectInitialContext(ContextInterface $initialContext)
    {
        $this->initialContext = $initialContext;
    }

    /**
     * Returns the initial context instance.
     *
     * @return \TechDivision\ApplicationServer\Interfaces\ContextInterface The initial Context
     */
    public function getInitialContext()
    {
        return $this->initialContext;
    }

    /**
     * Return's the applications available VHost configurations.
     *
     * @return array The available VHost configurations
     * @see \TechDivision\ServletEngine\WebContainerApplications::getVirtualHosts()
     * @deprecated Deprecated since version 0.6.0
     */
    public function getVhosts()
    {
        return $this->getVirtualHosts();
    }

    /**
     * Return's the applications available VHost configurations.
     *
     * @return array The available VHost configurations
     */
    public function getVirtualHosts()
    {
        return $this->virtualHosts;
    }

    /**
     * Returns the application name (that has to be the class namespace, e.g. TechDivision\Example)
     *
     * @return string The application name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the servlet engines base directory.
     *
     * @param string $directoryToAppend The directory to append to the base directory
     *
     * @return string The base directory with appended dir if given
     */
    public function getBaseDirectory($directoryToAppend = null)
    {
        $baseDirectory = $this->baseDirectory;
        if ($directoryToAppend != null) {
            $baseDirectory .= $directoryToAppend;
        }
        return $baseDirectory;
    }

    /**
     * Returns the absolute path to the directory containing the web applications.
     *
     * @return string The absolute path to the directory containing the web applications
     */
    public function getWebappPath()
    {
        return $this->getBaseDirectory($this->getAppBase() . DIRECTORY_SEPARATOR . $this->getName());
    }

    /**
     * Returns the path, relative to the base directory, containing the web applications.
     *
     * @return string The redirectory containing the web applications
     */
    public function getAppBase()
    {
        return $this->appBase;
    }

    /**
     * Has been automatically invoked by the container after the application
     * instance has been created.
     *
     * @return \TechDivision\ServletEngine\Application The connected application
     */
    public function connect()
    {

        try {
            
            // initialize the class loader with the additional folders
            set_include_path(get_include_path() . PATH_SEPARATOR . $this->getWebappPath());
            set_include_path(get_include_path() . PATH_SEPARATOR . $this->getWebappPath() . DIRECTORY_SEPARATOR . 'WEB-INF' . DIRECTORY_SEPARATOR . 'classes');
            set_include_path(get_include_path() . PATH_SEPARATOR . $this->getWebappPath() . DIRECTORY_SEPARATOR . 'WEB-INF' . DIRECTORY_SEPARATOR . 'lib');
    
            // initialize the servlet manager instance
            $servletContext = new ServletManager($this);
    
            // set the servlet manager
            $this->setServletContext($servletContext->initialize());
            
            // initialize the servlet locator instance
            $servletLocator = new ServletLocator($this->getServletContext());
            
            // set the servlet locator
            $this->setServletLocator($servletLocator);
            
        } catch (InvalidApplicationArchiveException $iaae) {
            // do nothing here, we simple doesn't have a web application
        }

        // return the instance itself
        return $this;
    }

    /**
     * Checks if the application is a virtual host for the passed server name.
     *
     * @param string $serverName The server name to check the application being a virtual host of
     *
     * @return boolean TRUE if the application is a virtual host, else FALSE
     * @see \TechDivision\ServletEngine\WebContainerApplications::isVirtualHostOf()
     * @deprecated Deprecated since version 0.6.0
     */
    public function isVhostOf($serverName)
    {
        return $this->isVirtualHostOf($serverName);
    }

    /**
     * Checks if the application is a virtual host for the passed server name.
     *
     * @param string $serverName The server name to check the application being a virtual host of
     *
     * @return boolean TRUE if the application is a virtual host, else FALSE
     */
    public function isVirtualHostOf($serverName)
    {
        
        // check if the application is a virtual host for the passed server name
        foreach ($this->getVirtualHosts() as $virtualHost) {
        
            // compare the virtual host name itself
            if (strcmp($virtualHost->getName(), $serverName) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Bounds the passed virtual host to the application.
     * 
     * @param \TechDivision\ServletEngine\VirtualHost $virtualHost The virtual host to be bounded
     * 
     * @return void
     */
    public function addVirtualHost(VirtualHost $virtualHost)
    {
        $this->virtualHosts[] = $virtualHost;
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
     * Sets the application's usable datasources.
     *
     * @param array $datasources The available datasources
     *
     * @return void
     */
    public function setDatasources($datasources)
    {
        $this->datasources = $datasources;
    }

    /**
     * Returns the application's usable datasources.
     *
     * @return array The available datasources
     */
    public function getDatasources()
    {
        return $this->datasources;
    }

    /**
     * Locates and returns the servlet instance that handles
     * the request passed as parameter.
     * 
     * @param \TechDivision\Servlet\Http\HttpServletRequest $servletRequest The request instance
     *
     * @return \TechDivision\Servlet\Servlet The servlet instance to handle the request
     */
    public function locate(HttpServletRequest $servletRequest)
    {
        
        // try to locate the servlet
        $servlet = $this->getServletLocator()->locate($servletRequest);
        
        // secure the servlet if necessary
        $this->secureServlet($servlet, $servletRequest->getServletPath());
        
        // return the servlet instance
        return $servlet;
    }

    /**
     * Check if the requested URI matches a secured url pattern and
     * secure the servlet with the configured authentication method.
     *
     * @param \TechDivision\Servlet\Servlet $servlet     A servlet instance
     * @param string                        $servletPath The servlet path information
     *
     * @return void
     */
    protected function secureServlet(Servlet $servlet, $servletPath)
    {
        // iterate over all servlets and return the matching one
        foreach ($this->getServletContext()->getSecuredUrlConfigs() as $securedUrlConfig) {
            list ($urlPattern, $auth) = array_values($securedUrlConfig);
            if (fnmatch($urlPattern, $servletPath)) {
                $servlet->injectSecuredUrlConfig($auth);
                $servlet->setAuthenticationRequired(true);
                break;
            }
        }
    }
    
    /**
     * Creates a new app node representation of this application.
     *
     * @return \TechDivision\ApplicationServer\Api\Node\AppNode The app node representation of the application
     * @deprecated Deprecated since 0.6.0
     * @see \TechDivision\ApplicationServer\Interfaces\ApplicationInterface::newAppNode()
     */
    public function newAppNode()
    {
        throw new \Exception(__METHOD__ . ' not implemented');
    }

    /**
     * Returns the app node representation of this application.
     *
     * @return \TechDivision\ApplicationServer\Api\Node\AppNode The node representation of the application
     * @deprecated Deprecated since 0.6.0
     * @see \TechDivision\ApplicationServer\Interfaces\ApplicationInterface::getAppNode()
     */
    public function getAppNode()
    {
        throw new \Exception(__METHOD__ . ' not implemented');
    }

    /**
     * Creates a new instance of the class with the passed name and arguments.
     *
     * @param string $className The fully qualified class name to return the instance for
     * @param array  $args      Arguments to pass to the constructor of the instance
     *
     * @return object The instance itself
     * @deprecated Deprecated since 0.6.0
     * @see \TechDivision\ApplicationServer\Interfaces\ApplicationInterface::newInstance()
     */
    public function newInstance($className, array $args = array())
    {
        throw new \Exception(__METHOD__ . ' not implemented');
    }
}
