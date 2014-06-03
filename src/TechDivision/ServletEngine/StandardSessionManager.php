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
 * @category Appserver
 * @package TechDivision_ServletEngine
 * @author Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link http://www.appserver.io
 */
class StandardSessionManager extends GenericStackable implements SessionManager
{

    /**
     * The default session prefix
     *
     * @var string
     */
    const SESSION_PREFIX = 'sess_';

    /**
     * Injects the session checksum storage to watch changed sessions.
     *
     * @param \TechDivision\Storage\StorageInterface $sessions The checksum session storage to use
     * @param \TechDivision\Storage\StorageInterface $checksums The checksum session storage to use
     *
     * @return void
     */
    public function __construct($sessions = null, $checksums = null)
    {

        // check if an storage for the session has been passed
        if ($sessions == null) {
            $sessions = new StackableStorage();
        }

        // check if an storage for the checksums has been passed
        if ($checksums == null) {
            $checksums = new StackableStorage();
        }

        // initialize the session and the checksum storage
        $this->sessions = $sessions;
        $this->checksums = $checksums;
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
    protected function getSessions()
    {
        return $this->sessions;
    }

    /**
     * Returns the session checksum storage to watch changed sessions..
     *
     * @return \TechDivision\Storage\StorageInterface The session checksum storage
     */
    protected function getChecksums()
    {
        return $this->checksums;
    }

    /**
     * Returns the session settings.
     *
     * @return \TechDivision\ServletEngine\SessionSettings The session settings
     */
    protected function getSettings()
    {
        return $this->settings;
    }

    /**
     * Returns the default path to persist sessions.
     *
     * @param string $toAppend A relative path to append to the session save path
     *
     * @return string The default path to persist session
     */
    protected function getSessionSavePath($toAppend = null)
    {
        // load the default path
        $sessionSavePath = $this->getSettings()->getSessionSavePath();

        // check if we've something to append
        if ($toAppend != null) {
            $sessionSavePath = $sessionSavePath . DIRECTORY_SEPARATOR . $toAppend;
        }

        // return the session save path
        return $sessionSavePath;
    }

    /**
     * Initializes the session manager instance.
     *
     * @return void
     */
    public function initialize()
    {

        // prepare the glob to load the session
        $glob = $this->getSessionSavePath(StandardSessionManager::SESSION_PREFIX . '*');

        // Iterate through all phar files and extract them to tmp dir
        foreach (new \GlobIterator($glob) as $sessionFile) {

            // if we found a file, it should be a session
            if ($sessionFile->isFile()) {

                // decode the session from the filesystem
                $jsonString = file_get_contents($sessionFile->getPathname());
                $session = Session::fromJson($jsonString);

                // attach the the reloaded session
                $this->attach($session);
            }
        }
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
    public function create($id, $sessionName, $lifetime = null, $maximumAge = null, $domain = null, $path = null, $secure = null, $httpOnly = null)
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
        if ($httpOnly) {
            $httpOnly = $this->getSettings()->getSessionCookieHttpOnly();
        }

        // initialize and return the session instance
        $session = new Session($id, $sessionName, $lifetime, $maximumAge, $domain, $path, $secure, $httpOnly);

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

        // load session ID and checksum
        $id = $session->getId();
        $checksum = $session->checksum();

        // register checksum + session
        $this->getChecksums()->set($id, $checksum);
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
    public function service()
    {
        $this->collectGarbage();
        $this->persist();
    }

    /**
     * Collects the session garbage.
     *
     * @return integer The number of expired and removed sessions
     */
    protected function collectGarbage()
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
                            $sessionRemovalCount ++;
                        }
                    }
                }
            }
        }

        // return the number of expired and removed sessions
        return $sessionRemovalCount;
    }

    /**
     * This method will be invoked by the engine after the
     * servlet has been serviced.
     *
     * @return void
     */
    protected function persist()
    {

        // iterate over all the checksums (session that are active and loaded)
        foreach ($this->getChecksums() as $id => $checksum) {

            // prepare the session filename
            $sessionFilename = $this->getSessionSavePath(StandardSessionManager::SESSION_PREFIX . $id);

            // check if we have that session
            if ($this->getSessions()->has($id)) {

                // if yes, try to load it
                $session = $this->getSessions()->get($id);

                // if we found a session
                if ($session instanceof ServletSession) {

                    // and it has changed
                    if ($checksum != $session->checksum()) {

                        // update the checksum and the file that stores the session data
                        file_put_contents($sessionFilename, json_encode($session));
                        $this->getChecksums()->set($id, $session->checksum());
                    }
                }

            } else { // if the session is not available anymore

                // delete the file containing the session data if available
                if (file_exists($sessionFilename)) {
                    unlink($sessionFilename);
                }

                // remove the checksum
                $this->getChecksums()->remove($id);
            }
        }
    }

    /**
     * Creates a random string with the passed length.
     *
     * @param integer $length The string lenght to generate
     *
     * @return string The random string
     */
    protected function generateRandomString($length = 32)
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
