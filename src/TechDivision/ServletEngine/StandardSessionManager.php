<?php

/**
 * TechDivision\ServletEngine\StandardSessionManager
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

use TechDivision\Servlet\Http\HttpSession;
use TechDivision\Servlet\Http\HttpServletRequest;
use TechDivision\Storage\StorageInterface;
use TechDivision\ServletEngine\Http\Session;
use TechDivision\ServletEngine\SessionSettings;
use TechDivision\Storage\StackableStorage;
use TechDivision\Storage\GenericStackable;
use TechDivision\Servlet\Http\Cookie;

/**
 * A standard session manager implementation that provides session
 * persistence while server has not been restarted.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class StandardSessionManager extends GenericStackable implements SessionManager
{

    /**
     * Injects the session storage to persist the sessions.
     *
     * @param \TechDivision\Storage\StorageInterface $sessions The session storage to use
     *
     * @return void
     */
    public function injectSessions(StorageInterface $sessions)
    {
        $this->sessions = $sessions;
    }

    /**
     * Injects the settings
     *
     * @param \TechDivision\ServletEngine\SessionSettings $settings Settings for the session handling
     *
     * @return void
     */
    public function injectSettings(SessionSettings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Returns all sessions actually attached to the session manager.
     *
     * @return \TechDivision\Storage\StorageInterface The container with sessions
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    /**
     * Returns the session settings.
     *
     * @return \TechDivision\ServletEngine\SessionSettings The session settings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * Creates a new session with the passed session ID and session name if give.
     *
     * @param string $id          The unique session ID to use
     * @param string $sessionName The name of the session to use
     *
     * @return \TechDivision\Servlet\HttpSession The requested session
     */
    public function create($id, $sessionName)
    {

        // copy the default session configuration from the settings
        $lifetime = $this->getSettings()->getSessionCookieLifetime();
        $maximumAge = $this->getSettings()->getSessionMaximumAge();
        $domain = $this->getSettings()->getSessionCookieDomain();
        $path = $this->getSettings()->getSessionCookiePath();
        $secure = $this->getSettings()->getSessionCookieSecure();
        $httpOnly = $this->getSettings()->getSessionCookieHttpOnly();

        // initialize and return the session instance
        $session = new Session($id, $sessionName, $lifetime, $maximumAge, $domain, $path, $secure, $httpOnly);

        // attach the session
        $this->attach($session);

        // return the session
        return $session;
    }

    /**
     * Attachs the passed session to the manager and returns the instance. If a session
     * with the session identifier already exists, it will be overwritten.
     *
     * @param \TechDivision\Servlet\Http\HttpSession $session The session to attach
     *
     * @return void
     */
    public function attach(HttpSession $session)
    {
        $this->sessions->set($session->getId(), $session);
    }

    /**
     * Tries to find a session for the given request. The session id will be
     * searched in the cookie header of the request, and in the request query
     * string. If both values are present, the value in the query string takes
     * precedence. If no session id is found, a new one is created and assigned
     * to the request.
     *
     * @param string $id The unique session ID to that has to be returned
     *
     * @return \TechDivision\Servlet\HttpSession The requested session
     */
    public function find($id)
    {
        // try to load the session with the passed ID
        if ($this->sessions->has($id)) {
            return $this->sessions->get($id);
        }
    }

    /**
     * Collects the session garbage.
     *
     * @return void
     */
    public function collectGarbage()
    {
        // some other values
        $collectionProbability = $this->getSettings()->getGarbageCollectionProbability();
        $inactivityTimeout = $this->getSettings()->getInactivityTimeout();

        // iterate over all session and collect the session garbage
        foreach ($this->sessions as $session) {
            // do collect garbage here
        }
    }

    /**
     * Creates a random string with the passed length.
     *
     * @param integer $length The string lenght to generate
     *
     * @return string The random string
     */
    public function generateRandomString($length = 32)
    {

        // prepare an array with the chars used to create a random string
        $letters = str_split('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');

        // create and return the random string
        $bytes = '';
        foreach (range(1, $length) as $i) {
            $bytes = $letters[mt_rand(0, sizeof($letters) - 1)] . $bytes;
        }

        // return the unique ID
        return $bytes;
    }
}
