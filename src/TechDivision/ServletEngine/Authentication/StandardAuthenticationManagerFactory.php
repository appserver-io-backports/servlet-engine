<?php

/**
 * TechDivision\ServletEngine\Authentication\StandardAuthenticationManagerFactory
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category   Appserver
 * @package    TechDivision_ServletEngine
 * @subpackage Authentication
 * @author     Florian Sydekum <fs@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */

namespace TechDivision\ServletEngine\Authentication;

use TechDivision\Storage\GenericStackable;
use TechDivision\ApplicationServer\AbstractManagerFactory;

/**
 * A factory for the standard session authentication manager instances.
 *
 * @category   Appserver
 * @package    TechDivision_ServletEngine
 * @subpackage Authentication
 * @author     Florian Sydekum <fs@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */
class StandardAuthenticationManagerFactory extends AbstractManagerFactory
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
                $initialContext = $self->initialContext;

                // register the default class loader
                $initialContext->getClassLoader()->register(true, true);

                // initialize the authentication manager
                $authenticationManager = new StandardAuthenticationManager();

                // attach the instance
                $instances[] = $authenticationManager;

                // wait for the next instance to be created
                $self->wait();

            }, $this);
        }
    }
}
