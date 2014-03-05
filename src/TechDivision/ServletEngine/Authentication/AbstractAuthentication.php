<?php

/**
 * TechDivision\ServletEngine\Authentication\AbstractAuthentication
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
 * @author     Philipp Dittert <pd@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */

namespace TechDivision\ServletEngine\Authentication;

use TechDivision\Servlet\Servlet;
use TechDivision\Servlet\ServletRequest;
use TechDivision\Servlet\ServletResponse;

/**
 * Abstract class for authentication adapters.
 *
 * @category   Appserver
 * @package    TechDivision_ServletEngine
 * @subpackage Authentication
 * @author     Philipp Dittert <pd@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */
abstract class AbstractAuthentication
{

    /**
     * Basic HTTP authentication method.
     * 
     * @var string
     */
    const AUTHENTICATION_METHOD_BASIC = 'Basic';

    /**
     * Digest HTTP authentication method.
     * 
     * @var string
     */
    const AUTHENTICATION_METHOD_DIGEST = 'Digest';

    /**
     * Holds the servlet instance.
     *
     * @var \TechDivision\Servlet\Servlet
     */
    protected $servlet;

    /**
     * Holds the Http servlet request instance.
     *
     * @var \TechDivision\Servlet\HttpServletRequest
     */
    protected $servletRequest;

    /**
     * Holds the Http servlet response instance.
     *
     * @var \TechDivision\Servlet\ServletResponse
     */
    protected $servletResponse;

    /**
     * An alternative constructor that has to be called manually.
     *
     * @param \TechDivision\Servlet\Servlet         $servlet         The servlet to process
     * @param \TechDivision\Servlet\ServletRequest  $servletRequest  The request instance
     * @param \TechDivision\Servlet\ServletResponse $servletResponse The response instance
     *
     * @return void
     */
    public function init(Servlet $servlet, ServletRequest $servletRequest, ServletResponse $servletResponse)
    {
        $this->setServlet($servlet);
        $this->setServletRequest($servletRequest);
        $this->setServletResponse($servletResponse);
    }

    /**
     * Sets the servlet instance.
     *
     * @param \TechDivision\Servlet\Servlet $servlet A servlet instance
     *
     * @return void
     */
    protected function setServlet($servlet)
    {
        $this->servlet = $servlet;
    }

    /**
     * Returns the servlet instance.
     *
     * @return \TechDivision\Servlet\Servlet
     */
    protected function getServlet()
    {
        return $this->servlet;
    }

    /**
     * Sets servlet request instance.
     *
     * @param \TechDivision\Servlet\ServletRequest $servletRequest The request instance
     *
     * @return void
     */
    protected function setServletRequest(ServletRequest $servletRequest)
    {
        $this->servletRequest = $servletRequest;
    }

    /**
     * Returns servlet request instance.
     *
     * @return \TechDivision\Servlet\ServletRequest The servlet request instance
     */
    protected function getServletRequest()
    {
        return $this->servletRequest;
    }

    /**
     * Sets servlet response instance.
     *
     * @param \TechDivision\Servlet\ServletResponse $servletResponse The response instance
     *
     * @return void
     */
    protected function setServletResponse(ServletResponse $servletResponse)
    {
        $this->servletResponse = $servletResponse;
    }

    /**
     * Returns servlet response instance.
     * 
     * @return \TechDivision\Servlet\ServletResponse The servlet response instance
     */
    protected function getServletResponse()
    {
        return $this->servletResponse;
    }
}
