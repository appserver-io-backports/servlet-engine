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
use TechDivision\Storage\StorageInterface;
use TechDivision\ServletEngine\Http\Session;
use TechDivision\ServletEngine\SessionSettings;
use TechDivision\Storage\StackableStorage;
use TechDivision\Storage\GenericStackable;

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
     *Initializes the internal member variables.
     *
     * @return void
     */
    public function __construct()
    {

        /**
         * The session settings.
         * @var \TechDivision\ServletEngine\Settings
         */
        $this->settings;

        /**
         * Cache storage for this session.
         * @var \TechDivision\Storage\StorageInterface
         */
        $this->storage;

        /**
         * Array to store the sessions that has already been initilized in this request.
         * @var \TechDivision\Storage\StorageInterface
         */
        $this->sessions;
    }

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
     * Injects the storage to persist session data.
     *
     * @param \TechDivision\Storage\StorageInterface $storage The session storage to use
     *
     * @return void
     */
    public function injectStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
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
     * Returns all sessions actually attached to the session manager.
     *
     * @return \TechDivision\Storage\StorageInterface The container with sessions
     */
    public function getStorage()
    {
        return $this->storage;
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
     * @param string|null $id          The session ID used to create the session
     * @param string|null $sessionName The name of the requested session
     *
     * @return \TechDivision\Servlet\HttpSession The requested session
     */
    public function create($id = null, $sessionName = null)
    {

        // initialize and return the session instance
        $session = new Session($id, time());
        $session->injectStorage($this->getStorage());

        // check if a session name has been specified
        if ($sessionName == null) { // if not, set the default session name
            $session->setSessionName($this->getSettings()->getSessionName());
        } else {
            $session->setSessionName($sessionName);
        }

        // copy the default session configuration from the settings
        $session->setSessionCookieLifetime($this->getSettings()->getSessionCookieLifetime());
        $session->setSessionCookieDomain($this->getSettings()->getSessionCookieDomain());
        $session->setSessionCookiePath($this->getSettings()->getSessionCookiePath());
        $session->setSessionCookieSecure($this->getSettings()->getSessionCookieSecure());
        $session->setSessionCookieHttpOnly($this->getSettings()->getSessionCookieHttpOnly());
        $session->setGarbageCollectionProbability($this->getSettings()->getGarbageCollectionProbability());
        $session->setInactivityTimeout($this->getSettings()->getInactivityTimeout());

        // attach the session to the manager and return it
        return $this->attach($session);
    }

    /**
     * Attachs the passed session to the manager and returns the instance. If a session
     * with the session identifier already exists, it will be overwritten.
     *
     * @param \TechDivision\Servlet\Http\HttpSession $session The session to attach
     *
     * @return \TechDivision\Servlet\Http\HttpSession The attached session
     */
    public function attach(HttpSession $session)
    {
        $this->sessions[] = $session;
        return $session;
    }

    /**
     * Tries to find a session for the given request. The session id will be
     * searched in the cookie header of the request, and in the request query
     * string. If both values are present, the value in the query string takes
     * precedence. If no session id is found, a new one is created and assigned
     * to the request.
     *
     * @param string|null $id          The ID of the session to find
     * @param string|null $sessionName The name of the requested session
     * @param boolean     $create      If TRUE, a new session will be created if the session with the passed ID can't be found
     *
     * @return \TechDivision\Servlet\HttpSession The requested session
     */
    public function find($id = null, $sessionName = null, $create = false)
    {

        // try to load the session with the passed ID
        foreach ($this->getSessions() as $session) {
            if ($session instanceof HttpSession && $session->isStarted() && $session->getId() === $id) {
                return $session;
            }
        }

        // create a new session with the requested session ID if requested
        if ($create === true) {
            return $this->create($id, $sessionName);
        }
    }
}
