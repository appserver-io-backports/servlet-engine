<?php

/**
 * TechDivision\ServletEngine\ServletManager
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
 * The servlet manager handles the servlets registered for the application.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class ServletManagerFactory
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

        // initialize the stackabls
        $servlets = new StackableStorage();
        $servletMappings = new StackableStorage();
        $initParameters = new StackableStorage();
        $securedUrlConfigs = new StackableStorage();
        $sessionParameters = new StackableStorage();

        // initialize the servlet locator
        $servletLocator = new ServletLocator();

        // initialize the servlet manager
        $servletManager = new ServletManager();
        $servletManager->injectServlets($servlets);
        $servletManager->injectApplication($application);
        $servletManager->injectServletMappings($servletMappings);
        $servletManager->injectInitParameters($initParameters);
        $servletManager->injectSecuredUrlConfigs($securedUrlConfigs);
        $servletManager->injectSessionParameters($sessionParameters);
        $servletManager->injectWebappPath($application->getWebappPath());
        $servletManager->injectResourceLocator($servletLocator);

        // attach the instance
        $application->addManager($servletManager);
    }
}
