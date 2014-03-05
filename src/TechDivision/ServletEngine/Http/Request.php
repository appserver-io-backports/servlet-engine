<?php

/**
 * TechDivision\ServletEngine\Request
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

use TechDivision\Http\HttpRequestInterface
use TechDivision\Servlet\Http\HttpServletRequest;

/**
 * A Http servlet request implementation.
 *
 * @category   Appserver
 * @package    TechDivision_ServletEngine
 * @subpackage Http
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */
class Request implements HttpServletRequest;
{
    
    /**
     * The servlet request instance.
     * 
     * @var \TechDivision\Http\HttpRequestInterface
     */
    protected $request;
    
    /**
     * Initializes the servlet request with the data of the passed 
     * Http request instance.
     * 
     * @param \TechDivision\Http\HttpRequestInterface $request The Http request instance
     * 
     * @return void
     */
    public function setRequest(HttpRequestInterface $request)
    {
        $this->request = $request;
    }
    
    /**
     * Returns the wrapped request object.
     * 
     * @return \TechDivision\Http\HttpRequestInterface The wrapped request object
     */
    public function getRequest()
    {
        return $this->request;
    }
    
    /**
     * Returns the host name passed with the request header.
     * 
     * @return string The host name of this request
     * @see \TechDivision\Servlet\ServletRequest::getServerName()
     */
    public function getServerName()
    {
        return $this->getRequest()->getServerName();
    }

    /**
     * Returns an part instance
     *
     * @return Part
     */
    public function getHttpPartInstance()
    {
        return $this->getRequest()->getHttpPartInstance();
    }

    /**
     * Returns an array with all request parameters.
     *
     * @return array The array with the request parameters
     */
    public function getParameterMap()
    {
        return $this->getRequest()->getParameterMap();
    }

    /**
     * Returns accepted encodings data
     *
     * @return array
     */
    public function getAcceptedEncodings()
    {
        return $this->getRequest()->getAcceptedEncodings();
    }

    /**
     * Returns the server's IP v4 address
     *
     * @return string
     */
    public function getServerAddress()
    {
        return $this->getRequest()->getServerAddress();
    }

    /**
     * Returns server port
     *
     * @return string
     */
    public function getServerPort()
    {
        return $this->getRequest()->getServerPort();
    }

    /**
     * Return content
     *
     * @return string $content
     */
    public function getContent()
    {
        return $this->getRequest()->getContent();
    }

    /**
     * Returns protocol version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->getRequest()->getVersion();
    }

    /**
     * Returns clients ip address
     *
     * @return mixed
     */
    public function getClientIp()
    {
        return $this->getRequest()->getClientIp();
    }

    /**
     * Returns clients port
     *
     * @return int
     */
    public function getClientPort()
    {
        return $this->getRequest()->getClientPort();
    }

    /**
     * Returns the parameter with the passed name if available or null
     * if the parameter not exists.
     *
     * @param string  $name   The name of the parameter to return
     * @param integer $filter The filter to use
     *
     * @return string|null
     */
    public function getParameter($name, $filter = FILTER_SANITIZE_STRING)
    {
        return $this->getRequest()->getParameter($name, $filter);
    }

    /**
     * Returns a part object by given name
     *
     * @param string $name The name of the form part
     *
     * @return \TechDivision\Http\HttpPart
     */
    public function getPart($name)
    {
        return $this->getRequest()->getPart($name);
    }

    /**
     * Returns the parts collection as array
     *
     * @return array A collection of HttpPart objects
     */
    public function getParts()
    {
        return $this->getRequest()->getParts();
    }
    
    /**
     * Returns the application context name (application name prefixed with a slash) for the actual request.
     * 
     * @return string The application context name
     */
    public function getContextPath()
    {
        return $this->getRequest()->getContextPath();
    }
    
    /**
     * Returns the path to the servlet used to handle this request.
     * 
     * @return string The relative path to the servlet
     */
    public function getServletPath()
    {
        return $this->getRequest()->getServletPath();
    }

    /**
     * Returns the session for this request.
     * 
     * @param boolean $create TRUE to create a new session, else FALSE
     *
     * @return \TechDivision\Servlet\Http\HttpSession The session instance
     */
    public function getSession($create = false)
    {
        return $this->getRequest()->getSession($create);
    }
    
    /**
     * Returns the absolute path info started from the context path.
     * 
     * @return string the absolute path info
     * @see \TechDivision\Servlet\ServletRequest::getPathInfo()
     */
    public function getPathInfo()
    {
        return $this->getRequest()->getPathInfo();
    }

    /**
     * Returns query string of the actual request.
     *
     * @return string|null The query string of the actual request
     */
    public function getQueryString()
    {
        return $this->getRequest()->getQueryString();
    }

    /**
     * Returns header info by given key
     *
     * @param string $key The header key to get
     *
     * @return string|null
     */
    public function getHeader($key)
    {
        return $this->getRequest()->getHeader($key);
    }

    /**
     * Returns headers data
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->getRequest()->getHeaders();
    }

    /**
     * Returns request method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->getRequest()->getMethod();
    }

    /**
     * Returns request uri
     *
     * @return string
     */
    public function getUri()
    {
        return $this->getRequest()->getUri();
    }

    /**
     * Returns true if the request has a cookie header with the passed
     * name, else false.
     *
     * @param string $cookieName Name of the cookie header to be checked
     *
     * @return boolean true if the request has the cookie, else false
     */
    public function hasCookie($cookieName)
    {
        return $this->getRequest()->hasCookie($cookieName);
    }

    /**
     * Returns the value of the cookie with the passed name.
     *
     * @param string $cookieName The name of the cookie to return
     *
     * @return mixed The cookie value
     */
    public function getCookie($cookieName)
    {
        return $this->getRequest()->getCookie($cookieName);
    }

    /**
     * Adds a header information got from connection.
     *
     * @param string $name  The header name
     * @param string $value The headers value
     *
     * @return void
     */
    public function addHeader($name, $value)
    {
        $this->getRequest()->addHeader($name, $value);
    }
    
    /**
     * Checks if header exists by given name.
     *
     * @param string $name The header name to check
     *
     * @return boolean
    */
    public function hasHeader($name)
    {
        $this->getRequest()->hasHeader($name);
    }
    
    /**
     * Sets all headers by given array.
     *
     * @param array $headers The headers to set
     *
     * @return void
    */
    public function setHeaders(array $headers)
    {
        $this->getRequest()->setHeaders($headers);
    }
    
    /**
     * Returns the real path to requested URI.
     *
     * @return string
    */
    public function getRealPath()
    {
        $this->getRequest()->getRealPath();
    }
    
    /**
     * Sets document root.
     *
     * @param string $documentRoot The document root
     *
     * @return void
    */
    public function setDocumentRoot($documentRoot)
    {
        $this->getRequest()->setDocumentRoot($documentRoot);
    }
    
    /**
     * Returns the document root.
     *
     * @return string
    */
    public function getDocumentRoot()
    {
        $this->getRequest()->getDocumentRoot();
    }
    
    /**
     * Initialises the request object to default properties.
     *
     * @return void
    */
    public function init()
    {
        $this->getRequest()->init();
    }
    
    /**
     * Sets requested URI.
     *
     * @param string $uri The requested URI to set
     *
     * @return void
    */
    public function setUri($uri)
    {
        $this->getRequest()->setUri($uri);
    }
    
    /**
     * Sets request method.
     *
     * @param string $method The method to set
     *
     * @return void
    */
    public function setMethod($method)
    {
        $this->getRequest()->setMethod($method);
    }
    
    /**
     * Sets parsed query string.
     *
     * @param string $queryString The parsed query string
     *
     * @return void
    */
    public function setQueryString($queryString)
    {
        $this->getRequest()->setQueryString($queryString);
    }
    
    /**
     * Sets body stream file descriptor resource.
     *
     * @param resource $bodyStream The body stream file descriptor resource
     *
     * @return void
    */
    public function setBodyStream($bodyStream)
    {
        $this->getRequest()->setBodyStream($bodyStream);
    }
    
    /**
     * Sets specific http version.
     *
     * @param string $version The version e.g. HTTP/1.1
     *
     * @return void
    */
    public function setVersion($version)
    {
        $this->getRequest()->setVersion($version);
    }
}
