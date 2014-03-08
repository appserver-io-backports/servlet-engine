<?php

/**
 * TechDivision\ServletEngine\Engine
 *
 * PHP version 5
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2013 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */

namespace TechDivision\ServletEngine;

use TechDivision\Http\HttpProtocol;
use TechDivision\Servlet\ServletRequest;
use TechDivision\Servlet\ServletResponse;
use TechDivision\WebServer\Dictionaries\ServerVars;
use TechDivision\ApplicationServer\Interfaces\ContainerInterface;

/**
 * The servlet engine implementation.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2013 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class Engine
{
    
    /**
     * A reference to the container instance.
     * 
     * @var TechDivision\ApplicationServer\Interfaces\ContainerInterface
     */
    protected $container;
    
    /**
     * Array with applications bound to this engine.
     * 
     * @var array
     */
    protected $applications;
    
    /**
     * The session manager instance.
     * 
     * @var \TechDivision\ServletEngine\SessionManager
     */
    protected $manager;

    /**
     * Initializes the engine.
     * 
     * @return void
     */
    public function init()
    {
    }
    
    /**
     * Processes the servlet request.
     *
     * @param \TechDivision\Servlet\ServletRequest  $servletRequest  The request instance to locate the application for
     * @param \TechDivision\Servlet\ServletResponse $servletResponse The response instance sent back to the client
     *
     * @return boolean
     */
    public function process(ServletRequest $servletRequest, ServletResponse $servletResponse)
    {

        // inject the response and the session manager into the request
        $servletRequest->injectResponse($servletResponse);
        $servletRequest->getContext()->injectSessionManager($this->getManager());
        
        // try to locate the application and the servlet that could service the current request
        $servlet = $this->locate($servletRequest)->locate($servletRequest);
        
        // initialize the default shutdown handler, and the authentication manager
        $authenticationManager = $this->newInstance('TechDivision\ServletEngine\AuthenticationManager');
        
        // inject authentication manager and shutdown handler
        $servlet->injectAuthenticationManager($authenticationManager);
        
        // let the servlet process the request send it back to the client
        $servlet->service($servletRequest, $servletResponse);
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
        
        // try to find the application by match it one of the prepared patterns
        foreach ($this->getApplications() as $pattern => $application) {
        
            // try to match a registered application with the passed request
            if (preg_match($pattern, $url) === 1) {
                
                // prepare and set the applications context path
                $servletRequest->setContextPath($contextPath = '/' . $application->getName());

                // prepare the path information depending if we're in a vhost or not
                if ($application->isVhostOf($host) === false) {
                    $servletRequest->setServletPath(str_replace($contextPath, '', $servletRequest->getServletPath()));
                }
                
                // return the application instance
                return $application;
            }
        }
        
        // if not throw a bad request exception
        throw new BadRequestException(
            sprintf('Can\'t find application for URI %s', $servletRequest->getUri())
        );
    }

    /**
     * Injects the container instance to use.
     * 
     * @param \TechDivision\ApplicationServer\Interfaces\ContainerInterface $container The container instance
     * 
     * @return void
     */
    public function injectContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Injects the applications bound to this engine
     * 
     * @param array $applications The applications bound to the engine
     * 
     * @return void
     */
    public function injectApplications(array $applications)
    {
        $this->applications = $applications;
    }

    /**
     * Injects the session manager instance to use.
     * 
     * @param \TechDivision\ServletEngine\SessionManager $manager The session manager instance
     * 
     * @return void
     */
    public function injectManager(SessionManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Returns the container instance.
     *
     * @return \TechDivision\ApplicationServer\Interfaces\ContainerInterface The container instance
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * Returns the session manager instance.
     *
     * @return \TechDivision\ServletEngine\SessionManager The session manager instance
     */
    protected function getManager()
    {
        return $this->manager;
    }
    
    /**
     * Returns the initialized applications bound to the engine.
     * 
     * @return array The array with the initialized applications
     */
    protected function getApplications()
    {
        return $this->applications;
    }

    /**
     * Returns the inital context instance.
     *
     * @return \TechDivision\ApplicationServer\InitialContext The initial context instance
     */
    protected function getInitialContext()
    {
        return $this->getContainer()->getInitialContext();
    }

    /**
     * Returns a new instance of the passed class name.
     *
     * @param string $className The fully qualified class name to return the instance for
     * @param array  $args      Arguments to pass to the constructor of the instance
     *
     * @return object The instance itself
     *
     * @see \TechDivision\ApplicationServer\InitialContext::newInstance()
     */
    protected function newInstance($className, array $args = array())
    {
        return $this->getInitialContext()->newInstance($className, $args);
    }
    
    /**
     * Register the class loader again, because in a thread the context 
     * lost all class loader information.
     * 
     * @return void
     */
    public function registerClassLoader()
    {
        $this->getInitialContext()->getClassLoader()->register(true);
    }
}
