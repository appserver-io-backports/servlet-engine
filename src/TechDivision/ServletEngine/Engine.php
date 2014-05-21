<?php

/**
 * TechDivision\ServletEngine\Engine
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

use TechDivision\Http\HttpProtocol;
use TechDivision\Servlet\ServletRequest;
use TechDivision\Servlet\ServletResponse;
use TechDivision\ApplicationServer\Interfaces\ContextInterface;
use TechDivision\ApplicationServer\Interfaces\ContainerInterface;

/**
 * The servlet engine implementation.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2013 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class Engine
{
    
    /**
     * Object storage with valves (modules) that'll handle the request.
     *
     * @var \SplObjectStorage
     */
    protected $valves;

    /**
     * Initializes the engine.
     *
     * @return void
     */
    public function init()
    {
    }
    
    /**
     * Processes the servlet request.
     *
     * @param \TechDivision\Servlet\ServletRequest  $servletRequest  The request instance to locate the application for
     * @param \TechDivision\Servlet\ServletResponse $servletResponse The response instance sent back to the client
     *
     * @return boolean
     */
    public function process(ServletRequest $servletRequest, ServletResponse $servletResponse)
    {
        foreach ($this->getValves() as $valve) {
            $valve->invoke($servletRequest, $servletResponse);
            if ($servletRequest->isDispatched() === true) {
                break;
            }
        }
    }
    
    /**
     * Injects the object storage with valves (modules) that'll handle the request.
     *
     * @param \SplObjectStorage $valves The valves (modules) that handles the request
     *
     * @return void
     */
    public function injectValves(\SplObjectStorage $valves)
    {
        $this->valves = $valves;
    }
    
    /**
     * The valves (modules) that handles the request.
     *
     * @return \SplObjectStorage The valves (modules) that handles the request
     */
    protected function getValves()
    {
        return $this->valves;
    }
}
