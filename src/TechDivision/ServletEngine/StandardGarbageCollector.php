<?php

/**
 * TechDivision\ServletEngine\StandardGarbageCollector
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
class StandardGarbageCollector extends \Thread implements GarbageCollector
{

    /**
     * The the sessions we want to handle garbage collection for.
     *
     * @var \TechDivision\Storage\StorageInterface
     */
    protected $sessions;

    /**
     * The the session settings.
     *
     * @var \TechDivision\ServletEngine\SessionSettings
     */
    protected $sessionSettings;

    /**
     * The flag that starts/stops the garbage collector.
     *
     * @var boolean
     */
    protected $run = false;

    /**
     * Initializes the session persistence manager with the session manager instance
     * we want to handle garbage collection for.
     *
     * @return void
     */
    public function __construct()
    {
        $this->run = true;
    }

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
     * Initializes and starts the garbage collector.
     *
     * @return void
     */
    public function initialize()
    {
    }

    /**
     * This is the main method that invokes the garbage collector.
     *
     * @return void
     */
    public function run()
    {
        while ($this->run) {
            $this->collectGarbage();
            sleep(5);
        }
    }

    /**
     * Returns the default path to persist sessions.
     *
     * @param string $toAppend A relative path to append to the session save path
     *
     * @return string The default path to persist session
     */
    private function getSessionSavePath($toAppend = null)
    {
        // load the default path
        $sessionSavePath = $this->getSessionSettings()->getSessionSavePath();

        // check if we've something to append
        if ($toAppend != null) {
            $sessionSavePath = $sessionSavePath . DIRECTORY_SEPARATOR . $toAppend;
        }

        // return the session save path
        return $sessionSavePath;
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
        $garbageCollectionProbability = $this->getSessionSettings()->getGarbageCollectionProbability();

        // calculate if the want to collect the garbage now
        $decimals = strlen(strrchr($garbageCollectionProbability, '.')) - 1;
        $factor = ($decimals > - 1) ? $decimals * 10 : 1;

        // if we can to collect the garbage, start collecting now
        if (rand(0, 100 * $factor) <= ($garbageCollectionProbability * $factor)) {

            // we want to know what inactivity timeout we've to check the sessions for
            $inactivityTimeout = $this->getSessionSettings()->getInactivityTimeout();

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

                            // then we remove the session checksum
                            $this->getChecksums()->remove($id);

                            // destroy the session if not already done
                            if ($session->getId() != null) {
                                $session->destroy(sprintf('Session %s was inactive for %s seconds, more than the configured timeout of %s seconds.', $session->getId(), $lastActivitySecondsAgo, $inactivityTimeout));

                            }
                            // prepare the session filename
                            $sessionFilename = $this->getSessionSavePath($this->getSessionSettings()->getSessionFilePrefix() . $id);

                            // delete the file containing the session data if available
                            if (file_exists($sessionFilename)) {
                                unlink($sessionFilename);
                            }

                            // raise the counter of expired session
                            $sessionRemovalCount ++;
                        }
                    }
                }
            }
        }
    }

    /**
     * Stops the garbage collector.
     *
     * @return void
     */
    public function stop()
    {
        $this->run = false;
    }
}
