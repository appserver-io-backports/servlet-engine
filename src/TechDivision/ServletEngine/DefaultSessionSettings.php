<?php

/**
 * TechDivision\ServletEngine\DefaultSessionSettings
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

use TechDivision\Servlet\Http\Cookie;

/**
 * Interface for all session storage implementation.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2013 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 * @link      http://php.net/session
 * @link      http://php.net/setcookie
 */
class DefaultSessionSettings implements SessionSettings
{

    /**
     * The default servlet session name.
     * 
     * @var string
     */
    const DEFAULT_SESSION_NAME = 'SESSID';
    
    /**
     * The default session cookie path.
     * 
     * @var string
     */
    const DEFAULT_SESSION_COOKIE_PATH = '/';
    
    /**
     * The session name to use.
     * 
     * @var string
     */
    protected $sessionName = DefaultSessionSettings::DEFAULT_SESSION_NAME;
    
    /**
     * The session cookie lifetime.
     * 
     * @var integer
     */
    protected $sessionCookieLifetime = 0;
    
    /**
     * The cookie domain set for the session.
     * 
     * @var string
     */
    protected $sessionCookieDomain = Cookie::LOCALHOST;
    
    /**
     * The cookie path set for the session.
     * 
     * @var string
     */
    protected $sessionCookiePath = DefaultSessionSettings::DEFAULT_SESSION_COOKIE_PATH;
    
    /**
     * The flag that the session cookie should only be set in a secure connection.
     * 
     * @var boolean
     */
    protected $sessionCookieSecure = false;
    
    /**
     * The flag if the session should set a Http only cookie.
     * 
     * @var boolean
     */
    protected $sessionCookieHttpOnly = false;
    
    /**
     * The probability the garbage collector will be invoked on the session.
     * 
     * @var float
     */
    protected $garbageCollectionProbability = 1.0;
    
    /**
     * The inactivity timeout until the session will be invalidated.
     * 
     * @var integer
     */
    protected $inactivityTimeout = 1440;
    
    /**
     * Initialize the default session settings.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->sessionCookieLifetime = time() + 86400;
    }

    /**
     * Set the session name
     *
     * @param $sessionName
     * @return void
     */
    public function setSessionName($sessionName)
    {
        $this->sessionName = $sessionName;
    }
    
    /**
     * Returns the session name to use.
     * 
     * @return string The session name
     */
    public function getSessionName()
    {
        return $this->sessionName;
    }
    
    /**
     * Returns the session cookie lifetime.
     * 
     * @return integer The session cookie lifetime
     */
    public function getSessionCookieLifetime()
    {
        return $this->sessionCookieLifetime;
    }
    
    /**
     * Returns the cookie domain set for the session.
     * 
     * @return string The cookie domain set for the session
     */
    public function getSessionCookieDomain()
    {
        return $this->sessionCookieDomain;
    }
    
    /**
     * Returns the cookie path set for the session.
     * 
     * @return string The cookie path set for the session
     */
    public function getSessionCookiePath()
    {
        return $this->sessionCookiePath;
    }
    
    /**
     * Returns the flag that the session cookie should only be set in a secure connection.
     * 
     * @return boolean TRUE if a secure cookie should be set, else FALSE
     */
    public function getSessionCookieSecure()
    {
        return $this->sessionCookieSecure;
    }
    
    /**
     * Returns the flag if the session should set a Http only cookie.
     * 
     * @return boolean TRUE if a Http only cookie should be used
     */
    public function getSessionCookieHttpOnly()
    {
        return $this->sessionCookieHttpOnly;
    }
    
    /**
     * Returns the probability the garbage collector will be invoked on the session.
     * 
     * @return float The garbage collector probability
     */
    public function getGarbageCollectionProbability()
    {
        return $this->garbageCollectionProbability;
    }
    
    /**
     * Returns the inactivity timeout until the session will be invalidated.
     * 
     * @return integer The inactivity timeout in seconds
     */
    public function getInactivityTimeout()
    {
        return $this->inactivityTimeout;
    }
}
