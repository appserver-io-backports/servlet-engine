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
use TechDivision\Servlet\ServletContext;
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
     * Injects the sessions.
     *
     * @param \TechDivision\Storage\StorageInterface $sessions The sessions
     *
     * @return void
     */
    public function injectSessions($sessions)
    {
        $this->sessions = $sessions;
    }

    /**
     * Injects the session factory.
     *
     * @param \TechDivision\ServletEngine\SessionFactory $sessionFactory The session factory
     *
     * @return void
     */
    public function injectSessionFactory($sessionFactory)
    {
        $this->sessionFactory = $sessionFactory;
    }

    /**
     * Injects the session settings.
     *
     * @param \TechDivision\ServletEngine\SessionSettings $sessionSettings Settings for the session handling
     *
     * @return void
     */
    public function injectSessionSettings($sessionSettings)
    {
        $this->sessionSettings = $sessionSettings;
    }

    /**
     * Injects the persistence manager.
     *
     * @param \TechDivision\ServletEngine\PersistenceManager $persistenceManager The persistence manager
     *
     * @return void
     */
    public function injectPersistenceManager(PersistenceManager $persistenceManager)
    {
        $this->persistenceManager = $persistenceManager;
    }

    /**
     * Injects the garbage collector.
     *
     * @param \TechDivision\ServletEngine\GarbageCollector $garbageCollector The garbage collector
     *
     * @return void
     */
    public function injectGarbageCollector(GarbageCollector $garbageCollector)
    {
        $this->garbageCollector = $garbageCollector;
    }

    /**
     * Injects the servlet manager.
     *
     * @param \TechDivision\Servlet\ServletContext $servletManager The servlet manager
     *
     * @return void
     */
    public function injectServletManager(ServletContext $servletManager)
    {
        $this->servletManager = $servletManager;
    }

    /**
     * Initializes the session manager.
     *
     * @return void
     */
    public function initialize()
    {

        // load the servlet manager with the session settings configured in web.xml
        $servletManager = $this->getServletManager();

        // prepare the default session save path
        $sessionSavePath = $servletManager->getWebappPath() . DIRECTORY_SEPARATOR . 'WEB-INF' . DIRECTORY_SEPARATOR . 'sessions';

        // load the settings, set the default session save path
        $sessionSettings = $this->getSessionSettings();
        $sessionSettings->setSessionSavePath($sessionSavePath);

        // if we've session parameters defined in our servlet context
        if ($servletManager->hasSessionParameters()) {

            // we want to merge the session settings from the servlet context
            $sessionSettings->mergeServletContext($servletManager);
        }

        // initialize the garbage collector and the persistence manager
        $this->getGarbageCollector()->initialize();
        $this->getPersistenceManager()->initialize();
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
    public function getSessionSettings()
    {
        return $this->sessionSettings;
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
     * Returns the servlet manager instance.
     *
     * @return \TechDivision\Servlet\ServletContext The servlet manager instance
     */
    public function getServletManager()
    {
        return $this->servletManager;
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

            // create a the actual date and add the cookie lifetime
            $dateTime = new \DateTime();
            $dateTime->modify("+{$this->getSessionSettings()->getSessionCookieLifetime()} second");

            // set the cookie lifetime as UNIX timestamp
            $lifetime = $dateTime->getTimestamp();
        }

        // copy the default session configuration for maximum from the settings
        if ($maximumAge == null) {
            $maximumAge = $this->getSessionSettings()->getSessionMaximumAge();
        }

        // copy the default session configuration for cookie domain from the settings
        if ($domain == null) {
            $domain = $this->getSessionSettings()->getSessionCookieDomain();
        }

        // copy the default session configuration for the cookie path from the settings
        if ($path == null) {
            $path = $this->getSessionSettings()->getSessionCookiePath();
        }

        // copy the default session configuration for the secure flag from the settings
        if ($secure == null) {
            $secure = $this->getSessionSettings()->getSessionCookieSecure();
        }

        // copy the default session configuration for the http only flag from the settings
        if ($httpOnly == null) {
            $httpOnly = $this->getSessionSettings()->getSessionCookieHttpOnly();
        }

        // initialize and return the session instance
        $session = $this->getSessionFactory()->nextFromPool();
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

    /**
     * Initializes the manager instance.
     *
     * @return void
     * @see \TechDivision\Application\Interfaces\ManagerInterface::initialize()
     */
    public function getIdentifier()
    {
        return SessionManager::IDENTIFIER;
    }

    /**
     * Returns the value with the passed name from the context.
     *
     * @param string $key The key of the value to return from the context.
     *
     * @return mixed The requested attribute
     */
    public function getAttribute($key)
    {
        throw new \Exception(sprintf('%s is not implemented yes', __METHOD__));
    }
}
