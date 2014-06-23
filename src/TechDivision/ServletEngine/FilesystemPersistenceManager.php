<?php

/**
 * TechDivision\ServletEngine\FilesystemPersistenceManager
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

use \TechDivision\Servlet\ServletSession;
use \TechDivision\Storage\StorageInterface;
use \TechDivision\Storage\StackableStorage;
use \TechDivision\ServletEngine\SessionFilter;

/**
 * A thread thats preinitialized session instances and adds them to the
 * the session pool.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class FilesystemPersistenceManager extends \Thread
{

    /**
     * The session manager instance we want to handle persistence for.
     *
     * @var \TechDivision\ServletEngine\SessionManager
     */
    protected $sessionManager;

    /**
     * The storage for the session checksums.
     *
     * @return \TechDivision\Storage\StorageInterface
     */
    protected $checksums;

    /**
     * The flag that starts/stops the persistence manager.
     *
     * @var boolean
     */
    protected $run = false;

    /**
     * Initializes the session persistence manager with the session manager instance
     * we want to handle session persistence for.
     *
     * @param \TechDivision\ServletEngine\SessionManager $sessionManager The session manager instance
     *
     * @return void
     */
    public function __construct(SessionManager $sessionManager)
    {

        // set the flag to start the persistence manager
        $this->run = true;

        // initialize session pool and size
        $this->sessionManager = $sessionManager;

        // initialize the checksums
        $this->checksums = new StackableStorage();

        // load the most actual sessions
        $this->initialize();

        // start the persistence manager
        $this->start();
    }

    /**
     * Returns the session checksum storage to watch changed sessions.
     *
     * @return \TechDivision\Storage\StorageInterface The session checksum storage
     */
    public function getChecksums()
    {
        return $this->checksums;
    }

    /**
     * Returns all sessions actually attached to the session manager.
     *
     * @return \TechDivision\Storage\StorageInterface The container with sessions
     */
    public function getSessions()
    {
        return $this->sessionManager->getSessions();
    }

    /**
     * Returns the session settings.
     *
     * @return \TechDivision\ServletEngine\SessionSettings The session settings
     */
    public function getSettings()
    {
        return $this->sessionManager->getSettings();
    }

    /**
     * This is the main method that handles session persistence.
     *
     * @return void
     */
    public function run()
    {
        while ($this->run) {
            $this->persist();
            sleep(1);
        }
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
        foreach ($this->getSessions() as $id => $session) {

            // prepare the session filename
            $sessionFilename = $this->getSessionSavePath($this->getSettings()->getSessionFilePrefix() . $id);

            // if we found a session
            if ($session instanceof ServletSession) {

                // if we don't have a checksum, this is a new session
                if ($this->checksums->has($id)) {
                    $checksum = $this->checksums->get($id);
                } else {
                    $checksum = null;
                }

                // and it has changed
                if ($session->getId() != null && $checksum != $session->checksum()) {
                    // update the checksum and the file that stores the session data
                    file_put_contents($sessionFilename, $this->transformSessionToJson($session));
                    $this->getChecksums()->set($id, $session->checksum());
                    continue;
                }

                // and it has changed, but the session has been destroyed
                if ($session->getId() == null && $checksum != $session->checksum()) {
                    // delete the file containing the session data if available
                    if (file_exists($sessionFilename)) {
                        unlink($sessionFilename);
                    }
                    continue;
                }

                // we want to know what inactivity timeout we've to check the sessions for
                $inactivityTimeout = $this->getSettings()->getInactivityTimeout();

                // load the sessions last activity timestamp
                $lastActivitySecondsAgo = time() - $session->getLastActivityTimestamp();

                // we want to detach the session (to free memory), when the last activity is > the inactivity timeout (1440 by default)
                if ($session->getId() != null && $checksum == $session->checksum() && $lastActivitySecondsAgo > $inactivityTimeout) {
                    // update the checksum and the file that stores the session data
                    file_put_contents($sessionFilename, $this->transformSessionToJson($session));
                    $this->getChecksums()->remove($id);
                    $this->getSessions()->remove($id);
                }
            }
        }
    }

    /**
     * Returns the default path to persist sessions.
     *
     * @param string $toAppend A relative path to append to the session save path
     *
     * @return string The default path to persist session
     */
    public function getSessionSavePath($toAppend = null)
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
    protected function initialize()
    {

        // prepare the glob to load the session
        $glob = $this->getSessionSavePath($this->getSettings()->getSessionFilePrefix() . '*');

        // we want to filter the session we initialize on server start
        $sessionFilter = new SessionFilter(new \GlobIterator($glob), $this->getSettings()->getInactivityTimeout());

        // iterate through all session files and initialize them
        foreach ($sessionFilter as $sessionFile) {

            // if we found a file, try to load the session data from the filesystem
            if ($sessionFile->isFile()) {
                $this->loadSessionFromFile($sessionFile->getPathname());
            }
        }
    }

    /**
     * Unpersists a session from the persistence layer and reattaches it to
     * the internal session storage.
     *
     * @return void
     */
    protected function unpersist($id)
    {

        // try to load the session with the passed ID
        if ($this->getSessions()->has($id) === false) {

            // prepare the pathname to the file containing the session data
            $pathname = $this->getSessionSavePath($this->getSettings()->getSessionFilePrefix() . $id);

            // if the file extists, load it
            if ($this->sessionFileExists($pathname)) {
                $this->loadSessionFromFile($pathname);
            }
        }
    }

    /**
     * Checks if a file with the passed name containing session data exists.
     *
     * @param string $pathname The path of the file to check
     *
     * @return boolean TRUE if the file exists, else FALSE
     */
    public function sessionFileExists($pathname)
    {
        return file_exists($pathname);
    }

    /**
     * Tries to load the session data from the passed filename.
     *
     * @param string $pathname The path of the file to load the session data from
     *
     * @return void
     * @throws \TechDivision\ServletEngine\SessionDataNotReadableException Is thrown if the file containing the session data is not readable or doesn't exists
     */
    public function loadSessionFromFile($pathname)
    {

        // the requested session file is not a valid file
        if ($this->sessionFileExists($pathname) === false) {
            throw new SessionDataNotReadableException(sprintf('Requested file % containing session data doesn\'t exists', $pathname));
        }

        // decode the session from the filesystem
        if (($jsonString = file_get_contents($pathname)) === false) {
            throw new SessionDataNotReadableException(sprintf('Can\'t load session data from file %s', $pathname));
        }

        // create a new session instance from the JSON string
        $session = $this->initSessionFromJson($jsonString);

        // load session ID and checksum
        $id = $session->getId();
        $checksum = $session->checksum();

        // add the sessions checksum
        $this->getChecksums()->set($id, $checksum);

        // initialize the session from the JSON string
        $this->sessionManager->attach($session);
    }

    /**
     * Initializes the session instance from the passed JSON string. If the encoded
     * data contains objects, they will be unserialized before reattached to the
     * session instance.
     *
     * @param string $jsonString The string containing the JSON data
     *
     * @return \TechDivision\Servlet\ServletSession The decoded session instance
     */
    public function initSessionFromJson($jsonString)
    {

        // decode the string
        $decodedSession = json_decode($jsonString);

        // extract the values
        $id = $decodedSession->id;
        $name = $decodedSession->name;
        $lifetime = $decodedSession->lifetime;
        $maximumAge = $decodedSession->maximumAge;
        $domain = $decodedSession->domain;
        $path = $decodedSession->path;
        $secure = $decodedSession->secure;
        $httpOnly = $decodedSession->httpOnly;
        $data = $decodedSession->data;

        // initialize the instance
        $session = $this->sessionManager->nextFromPool();
        $session->init($id, $name, $lifetime, $maximumAge, $domain, $path, $secure, $httpOnly);

        // append the session data
        foreach ($data as $key => $value) {
            $session->putData($key, unserialize($value));
        }

        // returns the session instance
        return $session;
    }

    /**
     * Transforms the passed session instance into a JSON encoded string. If the data contains
     * objects, each of them will be serialized before store them to the persistence layer.
     *
     * @param \TechDivision\Servlet\ServletSession $session The session to be transformed
     *
     * @return string The JSON encoded session representation
     */
    public function transformSessionToJson(ServletSession $session)
    {

        // create the stdClass (that can easy be transformed into an JSON object)
        $stdClass = new \stdClass();

        // copy the values to the stdClass
        $stdClass->id = $session->getId();
        $stdClass->name = $session->getName();
        $stdClass->lifetime = $session->getLifetime();
        $stdClass->maximumAge = $session->getMaximumAge();
        $stdClass->domain = $session->getDomain();
        $stdClass->path = $session->getPath();
        $stdClass->secure = $session->isSecure();
        $stdClass->httpOnly = $session->isHttpOnly();

        // initialize the array for the session data
        $stdClass->data = array();

        // append the session data
        foreach (get_object_vars($session->data) as $key => $value) {
            $stdClass->data[$key] = serialize($value);
        }

        // returns the JSON encoded session instance
        return json_encode($stdClass);
    }

    /**
     * Stops the peristence manager.
     *
     * @return void
     */
    public function stop()
    {
        $this->run = false;
    }
}
