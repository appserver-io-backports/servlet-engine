<?php

/**
 * TechDivision\ServletEngine\ServletManager
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

use TechDivision\Storage\StackableStorage;
use TechDivision\Servlet\Servlet;
use TechDivision\Servlet\ServletContext;
use TechDivision\Servlet\Http\HttpServletRequest;
use TechDivision\ServletEngine\ServletConfiguration;
use TechDivision\ServletEngine\InvalidServletMappingException;
use TechDivision\Application\Interfaces\ApplicationInterface;
use TechDivision\Application\Interfaces\ManagerConfigurationInterface;

/**
 * The servlet manager handles the servlets registered for the application.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class ServletManager extends \Stackable implements ServletContext
{

    /**
     * Initializes the queue manager.
     *
     * @return void
     */
    public function __construct()
    {

        // initialize the member variables
        $this->webappPath = '';
        $this->resourceLocator = null;

        // initialize the stackabls
        $this->servlets = new StackableStorage();
        $this->servletMappings = new StackableStorage();
        $this->initParameters = new StackableStorage();
        $this->securedUrlConfigs = new StackableStorage();
        $this->sessionParameters = new StackableStorage();
    }

    /**
     * Injects the absolute path to the web application.
     *
     * @param string $webappPath The path to this web application
     *
     * @return void
     */
    public function injectWebappPath($webappPath)
    {
        $this->webappPath = $webappPath;
    }

    /**
     * Injects the resource locator that locates the requested servlet.
     *
     * @param \TechDivision\ServletEngine\ResourceLocator $resourceLocator The resource locator
     *
     * @return void
     */
    public function injectResourceLocator(ResourceLocator $resourceLocator)
    {
        $this->resourceLocator = $resourceLocator;
    }

    /**
     * Has been automatically invoked by the container after the application
     * instance has been created.
     *
     * @param \TechDivision\Application\Interfaces\ApplicationInterface $application The application instance
     *
     * @return void
     * @see \TechDivision\Application\Interfaces\ManagerInterface::initialize()
     */
    public function initialize(ApplicationInterface $application)
    {
        $this->registerServlets();
    }

    /**
     * Finds all servlets which are provided by the webapps and initializes them.
     *
     * @return void
     */
    protected function registerServlets()
    {

        // the phar files have been deployed into folders
        if (is_dir($folder = $this->getWebappPath())) {

            // it's no valid application without at least the web.xml file
            if (!file_exists($web = $folder . DIRECTORY_SEPARATOR . 'WEB-INF' . DIRECTORY_SEPARATOR . 'web.xml')) {
                return;
            }

            // load the application config
            $config = new \SimpleXMLElement(file_get_contents($web));

            // intialize the security configuration by parseing the security nodes
            foreach ($config->xpath('/web-app/security') as $key => $securityParam) {
                // prepare the URL config in JSON format
                $securedUrlConfig = json_decode(json_encode($securityParam), 1);
                // add the web app path to the security config (to resolve relative filenames)
                $securedUrlConfig['webapp-path'] = $folder;
                // add the configuration to the array
                $this->securedUrlConfigs->set($key, $securedUrlConfig);
            }

            // initialize the context by parsing the context-param nodes
            foreach ($config->xpath('/web-app/context-param') as $contextParam) {
                $this->addInitParameter((string) $contextParam->{'param-name'}, (string) $contextParam->{'param-value'});
            }

            // initialize the session configuration by parsing the session-config childs
            foreach ($config->xpath('/web-app/session-config') as $sessionConfig) {
                foreach ($sessionConfig as $key => $value) {
                    $this->addSessionParameter(str_replace(' ', '', ucwords(str_replace('-', ' ', (string) $key))), (string) $value);
                }
            }

            // initialize the servlets by parsing the servlet-mapping nodes
            foreach ($config->xpath('/web-app/servlet') as $servlet) {

                // load the servlet name and check if it already has been initialized
                $servletName = (string) $servlet->{'servlet-name'};
                if (array_key_exists($servletName, $this->servlets)) {
                    continue;
                }

                // try to resolve the mapped servlet class
                $className = (string) $servlet->{'servlet-class'};
                if (!count($className)) {
                    throw new InvalidApplicationArchiveException(
                        sprintf('No servlet class defined for servlet %s', $servlet->{'servlet-class'})
                    );
                }

                // instantiate the servlet
                $instance = new $className();

                // initialize the servlet configuration
                $servletConfig = new ServletConfiguration();
                $servletConfig->injectServletContext($this);
                $servletConfig->injectServletName($servletName);

                // append the init params to the servlet configuration
                foreach ($servlet->{'init-param'} as $initParam) {
                    $servletConfig->addInitParameter((string) $initParam->{'param-name'}, (string) $initParam->{'param-value'});
                }

                // initialize the servlet
                $instance->init($servletConfig);

                // the servlet is added to the dictionary using the complete request path as the key
                $this->addServlet((string) $servlet->{'servlet-name'}, $instance);
            }

            // initialize the servlets by parsing the servlet-mapping nodes
            foreach ($config->xpath('/web-app/servlet-mapping') as $mapping) {

                // load the url pattern and the servlet name
                $urlPattern = (string) $mapping->{'url-pattern'};
                $servletName = (string) $mapping->{'servlet-name'};

                // the servlet is added to the dictionary using the complete request path as the key
                if (array_key_exists($servletName, $this->servlets) === false) {
                    throw new InvalidServletMappingException(
                        sprintf(
                            "Can't find servlet %s for url-pattern %s",
                            $servletName,
                            $urlPattern
                        )
                    );
                }

                // prepend the url-pattern - servlet mapping to the servlet mappings
                $this->servletMappings[$urlPattern] = $servletName;
            }
        }
    }

    /**
     * Sets all servlets as array
     *
     * @param array $servlets The servlets collection
     *
     * @return void
     */
    public function setServlets($servlets)
    {
        $this->servlets = $servlets;
    }

    /**
     * Returns all servlets
     *
     * @return array The servlets collection
     */
    public function getServlets()
    {
        return $this->servlets;
    }

    /**
     * Returns the servlet mappings found in the
     * configuration file.
     *
     * @return array The servlet mappings
     */
    public function getServletMappings()
    {
        return $this->servletMappings;
    }

    /**
     * Returns the resource locator for the servlets.
     *
     * @return \TechDivision\ServletEngine\ResourceLocator The resource locator for the servlets
     */
    public function getServletLocator()
    {
        return $this->servletLocator;
    }

    /**
     * Returns the servlet with the passed name.
     *
     * @param string $key The name of the servlet to return
     *
     * @return \TechDivision\Servlet\Servlet The servlet instance
     */
    public function getServlet($key)
    {
        if ($this->servlets->has($key)) {
            return $this->servlets->get($key);
        }
    }

    /**
     * Returns the servlet for the passed URL mapping.
     *
     * @param string $urlMapping The URL mapping to return the servlet for
     *
     * @return \TechDivision\Servlet\Servlet The servlet instance
     */
    public function getServletByMapping($urlMapping)
    {
        if ($this->servletMappings->has($urlMapping)) {
            return $this->getServlet($this->servletMappings->get($urlMapping));
        }
    }

    /**
     * Registers a servlet under the passed key.
     *
     * @param string                        $key     The servlet to key to register with
     * @param \TechDivision\Servlet\Servlet $servlet The servlet to be registered
     *
     * @return void
     */
    public function addServlet($key, Servlet $servlet)
    {
        $this->servlets->set($key, $servlet);
    }

    /**
     * Returns the path to the webapp.
     *
     * @return string The path to the webapp
     */
    public function getWebappPath()
    {
        return $this->webappPath;
    }

    /**
     * Return the resource locator instance.
     *
     * @return \TechDivision\ServletEngine\ResourceLocator The resource locator instance
     */
    public function getResourceLocator()
    {
        return $this->resourceLocator;
    }

    /**
     * Returns the host configuration.
     *
     * @return \TechDivision\ApplicationServer\Configuration The host configuration
     */
    public function getConfiguration()
    {
        throw new \Exception(__METHOD__ . ' not implemented');
    }

    /**
     * Registers the init parameter under the passed name.
     *
     * @param string $name  Name to register the init parameter with
     * @param string $value The value of the init parameter
     *
     * @return void
     */
    public function addInitParameter($name, $value)
    {
        $this->initParameters->set($name, $value);
    }

    /**
     * Returns the init parameter with the passed name.
     *
     * @param string $name Name of the init parameter to return
     *
     * @return null|string
     */
    public function getInitParameter($name)
    {
        if ($this->initParameters->has($name)) {
            return $this->initParameters->get($name);
        }
    }

    /**
     * Returns the webapps security context configurations.
     *
     * @return array The security context configurations
     */
    public function getSecuredUrlConfigs()
    {
        return $this->securedUrlConfigs;
    }

    /**
     * Registers the session parameter under the passed name.
     *
     * @param string $name  Name to register the session parameter with
     * @param string $value The value of the session parameter
     *
     * @return void
     */
    public function addSessionParameter($name, $value)
    {
        $this->sessionParameters->set($name, $value);
    }

    /**
     * Returns the session parameter with the passed name.
     *
     * @param string $name Name of the session parameter to return
     *
     * @return null|string
     */
    public function getSessionParameter($name)
    {
        if ($this->sessionParameters->has($name)) {
            return $this->sessionParameters->get($name);
        }
    }

    /**
     * Returns TRUE if we've at least one session parameter configured, else FALSE.
     *
     * @return boolean TRUE if we've at least one session parametr configured, else FALSE
     */
    public function hasSessionParameters()
    {
        return sizeof($this->sessionParameters) > 0;
    }

    /**
     * Tries to locate the resource related with the request.
     *
     * @param \TechDivision\Servlet\Http\HttpServletRequest $servletRequest The request instance to return the servlet for
     *
     * @return \TechDivision\Servlet\Servlet The requested servlet
     * @see \TechDivision\ServletEngine\ResourceLocator::locate()
     */
    public function locate(HttpServletRequest $servletRequest)
    {
        return $this->getResourceLocator()->locate($this, $servletRequest);
    }

    /**
     * Initializes the manager instance.
     *
     * @return void
     * @see \TechDivision\Application\Interfaces\ManagerInterface::initialize()
     */
    public function getIdentifier()
    {
        return ServletContext::IDENTIFIER;
    }

    /**
     * Returns the value with the passed name from the context.
     *
     * @param string $key The key of the value to return from the context.
     *
     * @return mixed The requested attribute
     */
    public function getAttribute($key)
    {
        throw new \Exception(sprintf('%s is not implemented yes', __METHOD__));
    }

    /**
     * Factory method that adds a initialized manager instance to the passed application.
     *
     * @param \TechDivision\Application\Interfaces\ApplicationInterface               $application          The application instance
     * @param \TechDivision\Application\Interfaces\ManagerConfigurationInterface|null $managerConfiguration The manager configuration
     *
     * @return void
     * @see \TechDivision\Application\Interfaces\ManagerInterface::get()
     */
    public static function visit(ApplicationInterface $application, ManagerConfigurationInterface $managerConfiguration = null)
    {

        // initialize the servlet locator
        $servletLocator = new ServletLocator();

        // initialize the servlet manager
        $servletManager = new ServletManager();
        $servletManager->injectWebappPath($application->getWebappPath());
        $servletManager->injectResourceLocator($servletLocator);

        // add the initialized manager instance to the application
        $application->addManager($servletManager);
    }
}
