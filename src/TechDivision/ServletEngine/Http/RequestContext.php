<?php

/**
 * TechDivision\ServletEngine\RequestContext
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
 * @subpackage Http
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */

namespace TechDivision\ServletEngine\Http;

use TechDivision\Context\Context;

/**
 * A Http servlet request interface.
 *
 * @category   Appserver
 * @package    TechDivision_ServletEngine
 * @subpackage Http
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */
interface RequestContext extends Context
{
    
    /**
     * Returns the session manager instance associated with this request.
     *
     * @return \TechDivision\ServletEngine\SessionManager The session manager instance
     */
    public function getSessionManager();
    
    /**
     * Returns the authentication manager instance associated with this request.
     *
     * @return \TechDivision\ServletEngine\AuthenticationManager The authentication manager instance
     */
    public function getAuthenticationManager();
}
