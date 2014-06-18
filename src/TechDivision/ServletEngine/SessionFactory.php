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
     * The session manager instance we're creating sessions for.
     *
     * @var \TechDivision\ServletEngine\SessionManager
     */
    protected $sessionManager;

    /**
     * The session pool storage.
     *
     * @var \TechDivision\Storage\StorageInterface
     */
    protected $sessionPool;

    /**
     * The session pool size.
     *
     * @var integer
     */
    protected $poolSize;

    /**
     * Initializes the session pool with the session pool storage.
     *
     * @param \TechDivision\ServletEngine\SessionManager $sessionManager The session pool storage
     * @param \TechDivision\Storage\StorageInterface     $sessionPool    The session pool storage
     * @param integer                                    $poolSize       The pool size we have to handle
     *
     * @return void
     */
    public function __construct(SessionManager $sessionManager, StorageInterface $sessionPool, $poolSize = 10)
    {

        // initialize session pool and size
        $this->sessionManager = $sessionManager;
        $this->sessionPool = $sessionPool;
        $this->poolSize = $poolSize;

        // refill the session pool
        $this->refill();

        // set the flag to start creating sessions
        $this->createThreads = true;

        // start the session factory
        $this->start();
    }

    /**
     * Initializes and adds a bunch of sessions to the session pool.
     *
     * @return void
     */
    protected function refill()
    {
        // add a new session to the pool
        for ($i = 0; $i < $this->poolSize; $i++) {
            $this->sessionPool->set($i, Session::emptyInstance());
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
        while ($this->createThreads) {

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
        $this->createThreads = false;
    }
}
