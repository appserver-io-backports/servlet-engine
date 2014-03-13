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
use TechDivision\Servlet\Http\HttpServletRequest;
use TechDivision\ApplicationServer\Api\Node\NodeInterface;
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
class WebContainerApplication implements Application
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
     * The app node the application is belonging to.
     *
     * @var \TechDivision\ApplicationServer\Api\Node\AppNode
     */
    protected $appNode;

    /**
     * The app node the application is belonging to.
     *
     * @var \TechDivision\ApplicationServer\Api\Node\ContainerNode
     */
    protected $containerNode;

    /**
     * The initial context instance.
     *
     * @var \TechDivision\ApplicationServer\Interfaces\ContextInterface
     */
    protected $initialContext;

    /**
     * Array with available virtual host configurations.
     * 
     * @var array
     */
    protected $virtualHosts = array();

    /**
     * The unique application name.
     *
     * @var string
     */
    protected $name;

    /**
     * The datasources the app might use.
     *
     * @var array
     */
    protected $datasources;

    /**
     * Passes the application name That has to be the class namespace.
     *
     * @param \TechDivision\ApplicationServer\Interfaces\ContextInterface $initialContext The initial context instance
     * @param \TechDivision\ApplicationServer\Api\Node\NodeInterface      $containerNode  The container node the deployment is for
     * @param string                                                      $name           The application name
     * @param array                                                       $datasources    The datasources the app might use
     *
     * @return void
     */
    public function __construct(ContextInterface $initialContext, NodeInterface $containerNode, $name, array $datasources = array())
    {
        $this->initialContext = $initialContext;
        $this->containerNode = $containerNode;
        $this->datasources = $datasources;
        $this->name = $name;
    }

    /**
     * Has been automatically invoked by the container after the application
     * instance has been created.
     *
     * @return \TechDivision\ServletEngine\Application The connected application
     */
    public function connect()
    {

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
     * Set's the app node the application is belonging to
     *
     * @param AppNode $appNode The app node the application is belonging to
     *
     * @return void
     */
    public function setAppNode($appNode)
    {
        $this->appNode = $appNode;
    }

    /**
     * Return's the app node the application is belonging to.
     *
     * @return AppNode The app node the application is belonging to
     */
    public function getAppNode()
    {
        return $this->appNode;
    }

    /**
     * Set's the app node the application is belonging to
     *
     * @param \TechDivision\ApplicationServer\Api\Node\NodeInterfacentainerNode $containerNode The container node the application is belonging to
     *
     * @return void
     */
    public function setContainerNode(NodeInterface $containerNode)
    {
        $this->containerNode = $containerNode;
    }

    /**
     * Return's the app node the application is belonging to.
     *
     * @return \TechDivision\ApplicationServer\Api\Node\NodeInterfacentainerNode The app node the application is belonging to
     */
    public function getContainerNode()
    {
        return $this->containerNode;
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
     * (non-PHPdoc)
     *
     * @param string $directoryToAppend The directory to append to the base directory
     *
     * @return string The base directory with appended dir if given
     * @see \TechDivision\ApplicationServer\Api\ContainerService::getBaseDirectory()
     */
    public function getBaseDirectory($directoryToAppend = null)
    {
        return $this
            ->newService('TechDivision\ApplicationServer\Api\ContainerService')
            ->getBaseDirectory($directoryToAppend);
    }

    /**
     * (non-PHPdoc)
     *
     * @return string The path to the webapps folder
     * @see \TechDivision\ApplicationServer\Api\ContainerService::getWebappPath()
     */
    public function getWebappPath()
    {
        return $this->getBaseDirectory($this->getAppBase() . DIRECTORY_SEPARATOR . $this->getName());
    }

    /**
     * (non-PHPdoc)
     *
     * @return string The app base
     * @see \TechDivision\ApplicationServer\Api\ContainerService::getAppBase()
     */
    public function getAppBase()
    {
        return $this->getContainerNode()->getHost()->getAppBase();
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
     * (non-PHPdoc)
     *
     * @return \TechDivision\ApplicationServer\Api\Node\AppNode The node representation of the application
     * @see \TechDivision\ServletEngine\Application:newAppNode()
     */
    public function newAppNode()
    {
        // create a new AppNode and initialize it with the values from this instance
        $appNode = $this->newInstance('TechDivision\ApplicationServer\Api\Node\AppNode');
        $appNode->setNodeName('application');
        $appNode->setName($this->getName());
        $appNode->setWebappPath($this->getWebappPath());
        $appNode->setDatasources($this->getDatasources());
        $appNode->setParentUuid($this->getContainerNode()->getParentUuid());
        $appNode->setUuid($appNode->newUuid());
        // set the AppNode in the instance itself and return it
        $this->setAppNode($appNode);

        return $appNode;
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $className The fully qualified class name to return the instance for
     * @param array  $args      Arguments to pass to the constructor of the instance
     *
     * @return object The instance itself
     * @see \TechDivision\ApplicationServer\Interfaces\ContextInterface::newInstance()
     */
    public function newInstance($className, array $args = array())
    {
        return $this->getInitialContext()->newInstance($className, $args);
    }

    /**
     * (non-PHPdoc)
     *
     * @param string $className The API service class name to return the instance for
     *
     * @return ServiceInterface The service instance
     * @see \TechDivision\ApplicationServer\Interfaces\ContextInterface::newService()
     */
    public function newService($className)
    {
        return $this->getInitialContext()->newService($className);
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
}
