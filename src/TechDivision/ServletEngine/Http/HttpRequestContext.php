<?php

/**
 * TechDivision\ServletEngine\Http\HttpRequestContext
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Appserver
 * @package    TechDivision_ServletEngine
 * @subpackage Http
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */

namespace TechDivision\ServletEngine\Http;

use TechDivision\Context\BaseContext;

/**
 * A Http servlet request context implementation.
 *
 * @category   Appserver
 * @package    TechDivision_ServletEngine
 * @subpackage Http
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */
class HttpRequestContext extends BaseContext
{
    
    /**
     * The session manager that is bound to the request.
     * 
     * @var \TechDivision\ServletEngine\SessionManager
     */
    protected $sessionManager;
    
    /**
     * The authentication manager that is bound to the request.
     * 
     * @var \TechDivision\ServletEngine\AuthenticationManager
     */
    protected $authenticationManager;
    
    /**
     * The server variables.
     * 
     * @var array
     */
    protected $serverVars = array();
    
    /**
     * Array with applications bound to this engine.
     * 
     * @var array
     */
    protected $applications;
    
    /**
     * Injects the session manager that is bound to the request.
     * 
     * @param \TechDivision\ServletEngine\SessionManager $sessionManager The session manager to bound this request to
     * 
     * @return void
     */
    public function injectSessionManager($sessionManager)
    {
        $this->sessionManager = $sessionManager;
    }
    
    /**
     * Injects the authentication manager that is bound to the request.
     * 
     * @param \TechDivision\ServletEngine\AuthenticationManager $authenticationManager The authentication manager to bound this request to
     * 
     * @return void
     */
    public function injectAuthenticationManager($authenticationManager)
    {
        $this->authenticationManager = $authenticationManager;
    }
    
    /**
     * Injects the server variables.
     * 
     * @param array $serverVars The server variables
     * 
     * @return void
     */
    public function injectServerVars(array $serverVars)
    {
        $this->serverVars = $serverVars;
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
     * Returns the session manager instance associated with this request.
     * 
     * @return \TechDivision\ServletEngine\SessionManager The session manager instance
     */
    public function getSessionManager()
    {
        return $this->sessionManager;
    }
    
    /**
     * Returns the authentication manager instance associated with this request.
     * 
     * @return \TechDivision\ServletEngine\AuthenticationManager The authentication manager instance
     */
    public function getAuthenticationManager()
    {
        return $this->authenticationManager;
    }
    
    /**
     * Returns the array with the server variables.
     * 
     * @return array The array with the server variables
     */
    public function getServerVars()
    {
        return $this->serverVars;
    }
    
    /**
     * Returns the initialized applications bound to the engine.
     * 
     * @return array The array with the initialized applications
     */
    public function getApplications()
    {
        return $this->applications;
    }
    
    /**
     * Returns the application bound to the acutal request.
     * 
     * @param string $contextPath The context path to return the application for
     * 
     * @return \TechDivision\ServletEngine\Authentication The application bound to this request
     */
    public function findApplicationByContextPath($contextPath)
    {
        foreach ($this->getApplications() as $application) {
            if ($application->getName() === ltrim($contextPath, '/')) {
                return $application;
            }
        }
    }

    /**
     * Returns the server variable with the requested name.
     *
     * @param string $name The name of the server variable to be returned
     *
     * @return mixed The requested server variable
     */
    public function getServerVar($name)
    {
        if (array_key_exists($name, $serverVars = $this->getServerVars())) {
            return $serverVars[$name];
        }
    }
}
