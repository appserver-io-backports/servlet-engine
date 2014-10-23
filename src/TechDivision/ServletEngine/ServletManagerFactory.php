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
use TechDivision\ApplicationServer\AbstractManagerFactory;

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
class ServletManagerFactory extends AbstractManagerFactory
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
                $servletManager->injectServletMappings($servletMappings);
                $servletManager->injectInitParameters($initParameters);
                $servletManager->injectSecuredUrlConfigs($securedUrlConfigs);
                $servletManager->injectSessionParameters($sessionParameters);
                $servletManager->injectWebappPath($application->getWebappPath());
                $servletManager->injectResourceLocator($servletLocator);

                // attach the instance
                $instances[] = $servletManager;

                // wait for the next instance to be created
                $self->wait();

            }, $this);
        }
    }
}
