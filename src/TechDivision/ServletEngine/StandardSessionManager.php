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
     * @param \TechDivision\Servlet\ServletSession $session The session to attach
     *
     * @return void
     */
    public function attach(ServletSession $session)
    {
        $this->getSessions()->set($session->getId(), $session);
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
        if ($this->getSessions()->has($id)) {
            // load the session with the passed ID
            $session = $this->getSessions()->get($id);
            // if we find a session, we've to check if it can be resumed
            if ($session->canBeResumed()) {
                $session->resume();
                return $session;
            }
        }
    }

    /**
     * Collects the session garbage.
     *
     * @return integer The number of expired and removed sessions
     */
    public function collectGarbage()
    {

        // counter to store the number of removed sessions
        $sessionRemovalCount = 0;

        // the probaility that we want to collect the garbage (float <= 1.0)
        $garbageCollectionProbability = $this->getSettings()->getGarbageCollectionProbability();

        // calculate if the want to collect the garbage now
        $decimals = strlen(strrchr($garbageCollectionProbability, '.')) - 1;
        $factor = ($decimals > - 1) ? $decimals * 10 : 1;

        // if we can to collect the garbage, start collecting now
        if (rand(0, 100 * $factor) <= ($garbageCollectionProbability * $factor)) {

            // we want to know what inactivity timeout we've to check the sessions for
            $inactivityTimeout = $this->getSettings()->getInactivityTimeout();

            // iterate over all session and collect the session garbage
            if ($inactivityTimeout !== 0) {

                // iterate over all sessions and remove the expired ones
                foreach ($this->getSessions() as $session) {

                    // check if we've a session instance
                    if ($session instanceof ServletSession) {

                        // load the sessions last activity timestamp
                        $lastActivitySecondsAgo = time() - $session->getLastActivityTimestamp();

                        // if session has been expired, destroy and remove it
                        if ($lastActivitySecondsAgo > $inactivityTimeout) {

                            // first we've to remove the session from the manager
                            $this->getSessions()->remove($session->getId());

                            // then we destroy the instance itself
                            $session->destroy(sprintf('Session %s was inactive for %s seconds, more than the configured timeout of %s seconds.', $session->getId(), $lastActivitySecondsAgo, $inactivityTimeout));

                            // raise the counter of expired session
                            $sessionRemovalCount++;
                        }
                    }
                }
            }
        }

        // return the number of expired and removed sessions
        return $sessionRemovalCount;
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
