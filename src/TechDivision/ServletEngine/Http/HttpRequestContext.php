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
     * The server variables.
     * 
     * @var array
     */
    protected $serverVars = array();
    
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
     * Injects the server variables.
     * 
     * @param array The server variables
     * 
     * @return void
     */
    public function injectServerVars(array $serverVars)
    {
        $this->serverVars = $serverVars;
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
     * Returns the array with the server variables.
     * 
     * @return array The array with the server variables
     */
    public function getServerVars()
    {
        return $this->serverVars;
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