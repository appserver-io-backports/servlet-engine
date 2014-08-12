<?php

/**
 * TechDivision\ServletEngine\Handler
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

use TechDivision\Storage\GenericStackable;
use TechDivision\Application\Interfaces\ApplicationInterface;

/**
 * Handler implementation.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class Handler extends GenericStackable
{

    /**
     * Initializes the handler with the necessary information.
     *
     * @param string $name The handler name
     *
     * @return void
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Returns the handler name.
     *
     * @return string The handler name
     */
    public function getName()
    {
        return $this->name;
    }
}
