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
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;

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
     * The session manager instance started with the engine.
     * 
     * @var \TechDivision\ServletEngine\SessionManager
     */
    protected $manager;
    
    /**
     * Array with applications handled by the servlet engine.
     * 
     * @var array
     */
    protected $applications;

    /**
     * Initializes the engine.
     *
     * @param \TechDivision\WebServer\Interfaces\ServerContextInterface $serverContext The servers context instance
     * 
     * @return void
     */
    public function init(ServerContextInterface $serverContext)
    {
        
        // load the container from the server context and deploy the applications
        $this->container = $serverContext->getContainer();
        $this->applications = $this->getDeployment()->deploy()->getApplications();
        
        // initialize the session manager
        $this->manager = $this->newInstance('TechDivision\ServletEngine\StandardSessionManager');
        $this->manager->injectSettings($this->newInstance('TechDivision\ServletEngine\DefaultSessionSettings'));
        $this->manager->injectStorage($this->getInitialContext()->getStorage());
    }
    
    /**
     * Processes the servlet request.
     *
     * @param \TechDivision\Http\HttpRequestInterface  $request  The request instance
     * @param \TechDivision\Http\HttpResponseInterface $response The response instance
     *
     * @return bool
     */
    public function process(HttpRequestInterface $request, HttpResponseInterface $response)
    {
        // intialize servlet session, request + response
        $servletRequest = $this->newInstance('TechDivision\ServletEngine\Http\Request', array($request));
        $servletResponse = $this->newInstance('TechDivision\ServletEngine\Http\Response', array($response));
        
        // inject servlet response and session manager
        $servletRequest->injectSessionManager($this->sessionManager);
        $servletRequest->injectServletResponse($servletResponse);
        
        // try to locate the application and the servlet that could service the current request
        $servlet = $this->locate($servletRequest)->locate($servletRequest);
        
        // initialize the default shutdown handler, and the authentication manager
        $shutdownHandler = $this->newInstance('TechDivision\Servlet\DefaultShutdownHandler', array($servletResponse));
        $authenticationManager = $this->newInstance('TechDivision\ServletEngine\AuthenticationManager');
        
        // inject authentication manager and shutdown handler
        $servlet->injectAuthenticationManager($authenticationManager);
        $servlet->injectShutdownHandler($shutdownHandler);
        
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
        
        // prepare the URI to be matched
        $url = $servletRequest->getServerName() . $servletRequest->getUri();
        
        // try to find the application by match it one of the prepared patterns
        foreach ($this->getApplications() as $pattern => $application) {
        
            // try to match a registered application with the passed request
            if (preg_match($pattern, $url) === 1) {
                
                // prepare and set the applications context path
                $servletRequest->setContextPath($contextPath = '/' . $application->getName());
                
                // prepare the path information depending if we're a vhost or not
                if ($application->isVhostOf($servletRequest->getServerName())) {
                    $pathInfo = $servletRequest->getUri();
                } else {
                    $pathInfo = str_replace($contextPath, '', $servletRequest->getUri());
                }
                
                // set the script file information in the server variables
                $servletRequest->setPathInfo($pathInfo);
                
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
     * Returns the with the initialized applications.
     * 
     * @return array The array with the initialized applications
     */
    protected function getApplications()
    {
        return $this->applications;
    }

    /**
     * Returns the deployment interface for the container for
     * this container thread.
     *
     * @return \TechDivision\ApplicationServer\Interfaces\DeploymentInterface The deployment instance for this container thread
     */
    protected function getDeployment()
    {
        return $this->newInstance(
            $this->getContainerNode()->getDeployment()->getType(),
            array(
                $this->getInitialContext(),
                $this->getContainerNode()
            )
        );
    }

    /**
     * Returns the container node.
     *
     * @return \TechDivision\ApplicationServer\Api\Node\ContainerNode The container node
     */
    protected function getContainerNode()
    {
        return $this->getContainer()->getContainerNode();
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
}
