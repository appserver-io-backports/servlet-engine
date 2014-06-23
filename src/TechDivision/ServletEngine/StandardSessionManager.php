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

use TechDivision\Servlet\ServletSession;
use TechDivision\Servlet\Http\Cookie;
use TechDivision\Servlet\Http\HttpSession;
use TechDivision\Servlet\Http\HttpServletRequest;
use TechDivision\ServletEngine\Http\Session;
use TechDivision\ServletEngine\SessionSettings;
use TechDivision\Storage\StorageInterface;
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
     * The seconds a session file has to be lastly accessed to be initialized when the server starts.
     *
     * @var integer
     */
    const SESSION_LAST_ACCESS_TIME = 1440;

    /**
     * Injects the session checksum storage to watch changed sessions.
     *
     * @param \TechDivision\ServletEngine\SessionSettings $settings Settings for the session handling
     *
     * @return void
     */
    public function __construct($settings)
    {

        $this->settings = $settings;

        // initialize the session and the checksum storage
        $this->sessions = new StackableStorage();

        // initialize the counter for the next session to load from the pool
        $this->nextSessionCounter = 0;

        // initialize the session pool
        $this->sessionPool = new StackableStorage();

        // start the session factory, persistence manager and garbage collector instances
        $this->sessionFactory = new SessionFactory($this);
        $this->persistenceManager = new FilesystemPersistenceManager($this);
        $this->garbageCollector = new GarbageCollector($this);
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
     * Returns the session factory.
     *
     * @return \TechDivision\ServletEngine\SessionFactory The session factory instance
     */
    public function getSessionFactory()
    {
        return $this->sessionFactory;
    }

    /**
     * Returns the session pool instance.
     *
     * @return \TechDivision\Storage\StorageInterface The session pool
     */
    public function getSessionPool()
    {
        return $this->sessionPool;
    }

    /**
     * Returns the persistence manager instance.
     *
     * @return \TechDivision\ServletEngine\FilesystemPersistenceManager The persistence manager instance
     */
    public function getPersistenceManager()
    {
        return $this->persistenceManager;
    }

    /**
     * Returns the garbage collector instance.
     *
     * @return \TechDivision\ServletEngine\GarbageCollector The garbage collector instance
     */
    public function getGarbageCollector()
    {
        return $this->garbageCollector;
    }

    /**
     * Load the next initialized session instance from the session pool.
     *
     * @return \TechDivision\Session\ServletSession The session instance
     */
    protected function nextFromPool()
    {

        // load the session factory
        $sessionFactory = $this->getSessionFactory();
        $sessionPool = $this->getSessionPool();

        // check the session counter
        if ($this->nextSessionCounter > (SessionFactory::SESSION_POOL_SIZE - 1)) {

            // notify the factory to create a new session instance
            $sessionFactory->notify();
            $this->wait();

            // reset the next session counter
            $this->nextSessionCounter = 0;
        }

        // return the next session instance from the pool
        return $sessionPool->get($this->nextSessionCounter++);
    }

    /**
     * Creates a new session with the passed session ID and session name if given.
     *
     * @param mixed            $id         The session ID
     * @param string           $name       The session name
     * @param integer|DateTime $lifetime   Date and time after the session expires
     * @param integer|null     $maximumAge Number of seconds until the session expires
     * @param string|null      $domain     The host to which the user agent will send this cookie
     * @param string           $path       The path describing the scope of this cookie
     * @param boolean          $secure     If this cookie should only be sent through a "secure" channel by the user agent
     * @param boolean          $httpOnly   If this cookie should only be used through the HTTP protocol
     *
     * @return \TechDivision\Servlet\ServletSession The requested session
     */
    public function create($id, $name, $lifetime = null, $maximumAge = null, $domain = null, $path = null, $secure = null, $httpOnly = null)
    {

        // copy the default session configuration for lifetime from the settings
        if ($lifetime == null) {
            $lifetime = $this->getSettings()->getSessionCookieLifetime();
        }

        // copy the default session configuration for maximum from the settings
        if ($maximumAge == null) {
            $maximumAge = $this->getSettings()->getSessionMaximumAge();
        }

        // copy the default session configuration for cookie domain from the settings
        if ($domain == null) {
            $domain = $this->getSettings()->getSessionCookieDomain();
        }

        // copy the default session configuration for the cookie path from the settings
        if ($path == null) {
            $path = $this->getSettings()->getSessionCookiePath();
        }

        // copy the default session configuration for the secure flag from the settings
        if ($secure == null) {
            $secure = $this->getSettings()->getSessionCookieSecure();
        }

        // copy the default session configuration for the http only flag from the settings
        if ($httpOnly == null) {
            $httpOnly = $this->getSettings()->getSessionCookieHttpOnly();
        }

        // initialize and return the session instance
        $session = $this->nextFromPool();
        $session->init($id, $name, $lifetime, $maximumAge, $domain, $path, $secure, $httpOnly);

        // attach the session with a random
        $this->attach($session);

        // return the session
        return $session;
    }

    /**
     * Attachs the passed session to the manager and returns the instance.
     * If a session
     * with the session identifier already exists, it will be overwritten.
     *
     * @param \TechDivision\Servlet\ServletSession $session The session to attach
     *
     * @return void
     */
    public function attach(ServletSession $session)
    {

        // load session ID
        $id = $session->getId();

        // register checksum + session
        $this->getSessions()->set($id, $session);
    }

    /**
     * Tries to find a session for the given request.
     * The session id will be
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

        // check if the session has already been loaded, if not try to unpersist it
        $this->getPersistenceManager()->unpersist($id);

        // load the session with the passed ID
        if ($session = $this->getSessions()->get($id)) {

            // if we find a session, we've to check if it can be resumed
            if ($session->canBeResumed()) {
                $session->resume();
                return $session;
            }
        }
    }
}
