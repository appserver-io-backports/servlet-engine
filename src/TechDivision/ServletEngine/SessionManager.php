<?php

/**
 * TechDivision\ServletEngine\SessionManager
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
use TechDivision\Servlet\Http\HttpSession;

/**
 * Interface for the session managers.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
interface SessionManager
{
    
    /**
     * Creates a new session with the passed session ID and session name if give.
     *
     * @param string $id The session ID used to create the session
     *
     * @return \TechDivision\Servlet\HttpSession The requested session
     */
    public function create($id);
    
    /**
     * Attachs the passed session to the manager and returns the instance. If a session
     * with the session identifier already exists, it will be overwritten.
     *
     * @param \TechDivision\Servlet\Http\HttpSession $session The session to attach
     *
     * @return \TechDivision\Servlet\Http\HttpSession The attached session
     */
    public function attach(HttpSession $session);

    /**
     * Tries to find a session for the given request. The session id will be
     * searched in the cookie header of the request, and in the request query
     * string. If both values are present, the value in the query string takes
     * precedence. If no session id is found, a new one is created and assigned
     * to the request.
     *
     * @param string  $id     The ID of the session to find
     * @param boolean $create If TRUE, a new session will be created if the session with the passed ID can't be found
     *
     * @return \TechDivision\Servlet\HttpSession The requested session
     */
    public function find($id, $create = false);
    
    /**
     * Returns all sessions actually attached to the session manager.
     *
     * @return array The array with sessions
     */
    public function getSessions();
}
