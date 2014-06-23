<?php

/**
 * TechDivision\ServletEngine\SessionFactory
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

use \TechDivision\Storage\StorageInterface;
use \TechDivision\ServletEngine\Http\Session;

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
class SessionFactory extends \Thread
{

    /**
     * The size of the sesion pool
     *
     * @var integer
     */
    const SESSION_POOL_SIZE = 10;

    /**
     * The session manager instance we're creating sessions for.
     *
     * @var \TechDivision\ServletEngine\SessionManager
     */
    protected $sessionManager;

    /**
     * Initializes the session pool with the session pool storage.
     *
     * @param \TechDivision\ServletEngine\SessionManager $sessionManager The session pool storage
     *
     * @return void
     */
    public function __construct(SessionManager $sessionManager)
    {

        // set the flag to start creating sessions
        $this->run = true;

        // initialize session pool and size
        $this->sessionManager = $sessionManager;

        // refill the session pool
        $this->refill();

        // start the session factory
        $this->start();
    }

    /**
     * Returns the session pool instance.
     *
     * @return \TechDivision\Storage\StorageInterface The session pool
     */
    public function getSessionPool()
    {
        return $this->sessionManager->getSessionPool();
    }

    /**
     * Initializes and adds a bunch of sessions to the session pool.
     *
     * @return void
     */
    protected function refill()
    {
        // add a new session to the pool
        for ($i = 0; $i < SessionFactory::SESSION_POOL_SIZE; $i++) {
            $this->getSessionPool()->set($i, Session::emptyInstance());
        }
    }

    /**
     * This is the main factory method that creates the new
     * session instances and adds them to the session pool.
     *
     * @return void
     */
    public function run()
    {

        // create a local reference to the session manager
        $sessionManager = $this->sessionManager;

        // while we should create threads, to it
        while ($this->run) {

            // we wait for the session manager to be notfied
            $this->wait();

            // refill the session pool
            $this->refill();

            // we've to notify that we've created the sessions
            $sessionManager->notify();
        }
    }

    /**
     * Stops the session factory.
     *
     * @return void
     */
    public function stop()
    {
        $this->run = false;
    }
}
