<?php
/**
 * TechDivision\ServletEngine\SessionSettings
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
interface SessionSettings
{
    
    /**
     * Returns the session name.
     * 
     * @return string
     */
    public function getSessionName();
    
    /**
     * Returns the session cookie lifetime.
     * 
     * @return integer
     */
    public function getSessionCookieLifetime();
    
    /**
     * Returns the cookie domain set for the session.
     * 
     * @return string
     */
    public function getSessionCookieDomain();
    
    /**
     * Returns the cookie path set for the session.
     * 
     * @return string
     */
    public function getSessionCookiePath();
    
    /**
     * Returns the flag that the session cookie should only be set in a secure connection.
     * 
     * @return boolean TRUE if a secure cookie should be set, else FALSE
     */
    public function getSessionCookieSecure();
    
    /**
     * Returns the flag if the session should set a Http only cookie.
     * 
     * @return boolean TRUE if a Http only cookie should be used
     */
    public function getSessionCookieHttpOnly();
    
    /**
     * Returns the probability the garbage collector will be invoked on the session.
     * 
     * @return float The garbage collector probability
     */
    public function getGarbageCollectionProbability();
    
    /**
     * Returns the inactivity timeout until the session will be invalidated.
     * 
     * @return integer The inactivity timeout in seconds
     */
    public function getInactivityTimeout();
}
