<?php

/**
 * TechDivision\ServletEngine\Http\HttpServlet
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

use TechDivision\Http\HttpProtocol;
use TechDivision\Servlet\GenericServlet;
use TechDivision\Servlet\ServletRequest;
use TechDivision\Servlet\ServletResponse;
use TechDivision\Servlet\ServletException;
use TechDivision\Servlet\Http\HttpServlet;
use TechDivision\ServletEngine\AuthenticationManager;

/**
 * Abstract servlet engine specific Http servlet implementation.
 *
 * @category   Appserver
 * @package    TechDivision_ServletEngine
 * @subpackage Http
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */
abstract class Servlet extends HttpServlet
{

    /**
     * Holds the authentication manager
     *
     * @var \TechDivision\ServletEngine\AuthenticationManager
     */
    protected $authenticationManager;

    /**
     * Holds the flag if authentication is required for specific servlet.
     *
     * @var boolean
     */
    protected $authenticationRequired;

    /**
     * Holds the configured security configuration.
     *
     * @var array
     */
    protected $securedUrlConfig;

    /**
     * Delegation method for specific Http methods.
     *
     * @param \TechDivision\Servlet\ServletRequest  $servletRequest  The request instance
     * @param \TechDivision\Servlet\ServletResponse $servletResponse The response instance
     * 
     * @return void
     */
    public function service(ServletRequest $servletRequest, ServletResponse $servletResponse)
    {

        // check if servlet needs authentication and return if authentication is not provided.
        if ($this->getAuthenticationRequired() && !$this->getAuthenticationManager()->handleRequest($servletRequest, $servletResponse, $this)) {
            return;
        }
        
        // call parent servlets method to service this request
        parent::service($servletRequest, $servletResponse);
    }

    /**
     * Injects the authentication manager.
     *
     * @param \TechDivision\ServletEngine\AuthenticationManager $authenticationManager An authentication manager instance
     *
     * @return void
     */
    public function injectAuthenticationManager(AuthenticationManager $authenticationManager)
    {
        $this->authenticationManager = $authenticationManager;
    }

    /**
     * Injects the security configuration.
     *
     * @param array $configuration The configuration array
     *
     * @return void
     */
    public function injectSecuredUrlConfig($configuration)
    {
        $this->securedUrlConfig = $configuration;
    }

    /**
     * Returns the injected authentication manager.
     *
     * @return \TechDivision\ServletEngine\AuthenticationManager
     */
    public function getAuthenticationManager()
    {
        return $this->authenticationManager;
    }

    /**
     * Sets the authentication required flag.
     *
     * @param boolean $authenticationRequired The flag if authentication is required
     *
     * @return void
     */
    public function setAuthenticationRequired($authenticationRequired)
    {
        $this->authenticationRequired = $authenticationRequired;
    }

    /**
     * Returns the authentication required flag.
     *
     * @return boolean TRUE if authentication is required, else FALSE
     */
    public function getAuthenticationRequired()
    {
        // This might not be set by default, so we will return false as our default
        if (!isset($this->authenticationRequired)) {
            return false;
        } else {
            return $this->authenticationRequired;
        }
    }

    /**
     * Returns the security configuration.
     *
     * @return array The configuration
     */
    public function getSecuredUrlConfig()
    {
        return $this->securedUrlConfig;
    }
}
