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
use TechDivision\Application\Interfaces\ApplicationInterface;
use TechDivision\Application\Interfaces\ManagerConfigurationInterface;

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
class StandardSessionManagerFactory
{

    /**
     * The main method that creates new instances in a separate context.
     *
     * @param \TechDivision\Application\Interfaces\ApplicationInterface          $application          The application instance to register the class loader with
     * @param \TechDivision\Application\Interfaces\ManagerConfigurationInterface $managerConfiguration The manager configuration
     *
     * @return void
     */
    public static function visit(ApplicationInterface $application, ManagerConfigurationInterface $managerConfiguration)
    {

        // load the registered loggers
        $loggers = $application->getInitialContext()->getLoggers();

        // initialize the session pool
        $sessions = new StackableStorage();
        $checksums = new StackableStorage();
        $sessionPool = new StackableStorage();
        $sessionSettings = new DefaultSessionSettings();
        $sessionMarshaller = new StandardSessionMarshaller();

        // we need a session factory instance
        $sessionFactory = new SessionFactory($sessionPool);
        $sessionFactory->injectLoggers($loggers);
        $sessionFactory->start();

        // we need a persistence manager and garbage collector
        $persistenceManager = new FilesystemPersistenceManager();
        $persistenceManager->injectLoggers($loggers);
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
        $garbageCollector->injectLoggers($loggers);
        $garbageCollector->injectSessions($sessions);
        $garbageCollector->injectSessionFactory($sessionFactory);
        $garbageCollector->injectSessionSettings($sessionSettings);
        $garbageCollector->start();

        // and finally we need the session manager instance
        $sessionManager = new StandardSessionManager();
        $sessionManager->injectSessions($sessions);
        $sessionManager->injectSessionSettings($sessionSettings);
        $sessionManager->injectSessionFactory($sessionFactory);
        $sessionManager->injectPersistenceManager($persistenceManager);
        $sessionManager->injectGarbageCollector($garbageCollector);

        // attach the instance
        $application->addManager($sessionManager, $managerConfiguration);
    }
}
