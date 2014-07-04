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
     * Initializes the internal session counter.
     *
     * @var integer
     */
    protected $nextSessionCounter;

    /**
     * The session manager instance we're creating sessions for.
     *
     * @var \TechDivision\ServletEngine\SessionManager
     */
    protected $sessionPool;

    /**
     * Initializes the session factory instance.
     *
     * @param \TechDivision\Storage\StorageInterface $sessionPool The session pool
     */
    public function __construct(StorageInterface $sessionPool)
    {
        // initialize the members
        $this->run = true;
        $this->nextSessionCounter = 0;

        // set the session pool storage
        $this->sessionPool = $sessionPool;

        // initialize the session pool
        $this->refill();
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
     * Load the next initialized session instance from the session pool.
     *
     * @return \TechDivision\Session\ServletSession The session instance
     */
    protected function nextFromPool()
    {

        // check the session counter
        if ($this->nextSessionCounter > (SessionFactory::SESSION_POOL_SIZE - 1)) {

            // notify the factory to create a new session instances
            $this->notify();

            // reset the next session counter
            $this->nextSessionCounter = 0;
        }


        // return the next session instance from the pool
        return $this->getSessionPool()->get($this->nextSessionCounter++);
    }

    /**
     * This is the main factory method that creates the new
     * session instances and adds them to the session pool.
     *
     * @return void
     */
    public function run()
    {

        // while we should create threads, to it
        while ($this->run) {
            $this->wait();
            $this->refill();
        }
    }

    /**
     * Refills the session pool.
     *
     * @return void
     */
    protected function refill()
    {
        for ($i = 0; $i < SessionFactory::SESSION_POOL_SIZE; $i++) {
            $this->getSessionPool()->set($i, Session::emptyInstance());
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
