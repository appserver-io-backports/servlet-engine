<?php

/**
 * TechDivision\ServletEngine\ResourceLocator
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

use TechDivision\Servlet\ServletRequest;
use TechDivision\Servlet\ServletContext;

/**
 * Interface for the resource locator instances.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
interface ResourceLocator
{

    /**
     * Tries to locate the resource related with the request.
     *
     * @param \TechDivision\Servlet\ServletContext $servletContext The servlet context that handles the servlets
     * @param \TechDivision\Servlet\ServletRequest $servletRequest The request instance to return the servlet for
     *
     * @return \TechDivision\Servlet\Servlet The requested servlet
     */
    public function locate(ServletContext $servletContext, ServletRequest $servletRequest);
}
