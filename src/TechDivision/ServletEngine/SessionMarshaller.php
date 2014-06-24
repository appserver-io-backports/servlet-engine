<?php

/**
 * TechDivision\ServletEngine\SessionMarshaller
 *
 * PHP version 5
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2013 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */

namespace TechDivision\ServletEngine;

use TechDivision\Servlet\ServletSession;

/**
 * Interface for all session marshaller implementations.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2013 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 * @link      http://php.net/session
 * @link      http://php.net/setcookie
 */
interface SessionMarshaller
{

    /**
     * Marshalls the passed object.
     *
     * @param \TechDivision\Session\ServletSession $servletSession The session we want to marshall
     *
     * @return string The marshalled session representation
     */
    public function marshall(ServletSession $servletSession);

    /**
     * Unmarshalls the marshalled session representation.
     *
     * @param \TechDivision\Session\ServletSession $servletSession The empty session instance we want the unmarshalled data be added to
     * @param string                               $marshalled     The marshalled session representation
     *
     * @return void
     */
    public function unmarshall(ServletSession $servletSession, $marshalled);
}
