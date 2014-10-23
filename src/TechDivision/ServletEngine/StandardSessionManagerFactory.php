<?php

/**
 * TechDivision\ServletEngine\StandardSessionManagerFactory
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

use TechDivision\Storage\StackableStorage;
use TechDivision\ApplicationServer\AbstractManagerFactory;

/**
 * A factory for the standard session manager instances.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class StandardSessionManagerFactory extends AbstractManagerFactory
{

    /**
     * The main method that creates new instances in a separate context.
     *
     * @return void
     */
    public function run()
    {

        while (true) { // we never stop

            $this->synchronized(function ($self) {

                // make instances local available
                $instances = $self->instances;
                $application = $self->application;
                $initialContext = $self->initialContext;

                // register the default class loader
                $initialContext->getClassLoader()->register(true, true);

                // initialize the session pool
                $sessions = new StackableStorage();
                $checksums = new StackableStorage();
                $sessionPool = new StackableStorage();
                $sessionSettings = new DefaultSessionSettings();
                $sessionMarshaller = new StandardSessionMarshaller();

                // we need a session factory instance
                $sessionFactory = new SessionFactory($sessionPool);

                // we need a persistence manager and garbage collector
                $persistenceManager = new FilesystemPersistenceManager();
                $persistenceManager->injectSessions($sessions);
                $persistenceManager->injectChecksums($checksums);
                $persistenceManager->injectSessionSettings($sessionSettings);
                $persistenceManager->injectSessionMarshaller($sessionMarshaller);
                $persistenceManager->injectSessionFactory($sessionFactory);
                $persistenceManager->injectUser($application->getUser());
                $persistenceManager->injectGroup($application->getGroup());
                $persistenceManager->injectUmask($application->getUmask());
                $persistenceManager->start();

                // we need a garbage collector
                $garbageCollector = new StandardGarbageCollector();
                $garbageCollector->injectSessions($sessions);
                $garbageCollector->injectSessionSettings($sessionSettings);
                $garbageCollector->start();

                // and finally we need the session manager instance
                $sessionManager = new StandardSessionManager();
                $sessionManager->injectSessions($sessions);
                $sessionManager->injectSessionSettings($sessionSettings);
                $sessionManager->injectSessionFactory($sessionFactory);
                $sessionManager->injectPersistenceManager($persistenceManager);
                $sessionManager->injectGarbageCollector($garbageCollector);

                // start the session factory
                $sessionFactory->start();

                // attach the instance
                $instances[] = $sessionManager;

                // wait for the next instance to be created
                $self->wait();

            }, $this);
        }
    }
}
