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

use TechDivision\Context\Context;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Servlet\Http\Cookie;
use TechDivision\Servlet\Http\HttpServletRequest;
use TechDivision\Servlet\Http\HttpServletResponse;

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
class Request implements HttpServletRequest
{

    /**
     * Request header data.
     *
     * @var array
     */
    protected $headers = array();

    /**
     * Array that contain's the cookies passed with.
     * the request.
     *
     * @var array
     */
    protected $cookies = array();

    /**
     * An array that contains all request parameters.
     *
     * @var array
     */
    protected $parameterMap = array();
    
    /**
     * The array with the server variables.
     * 
     * @var array
     */
    protected $serverVars = array();
    
    /**
     * The ID of requested session.
     * 
     * @var string
     */
    protected $requestedSessionId;

    /**
     * Path info.
     *
     * @var string
     */
    protected $pathInfo;

    /**
     * The request method.
     *
     * @var string
     */
    protected $method;

    /**
     * The request body.
     *
     * @var string
     */
    protected $bodyStream;

    /**
     * Uri called by client.
     *
     * @var string
     */
    protected $uri;

    /**
     * Protocol version.
     *
     * @var string
     */
    protected $version;

    /**
     * Query string with params.
     *
     * @var string
     */
    protected $queryString;
    
    /**
     * The path to the servlet used to handle the request.
     * 
     * @var string
     */
    protected $servletPath;

    /**
     * Hold's the document root directory
     *
     * @var string
     */
    protected $documentRoot;
    
    /**
     * The context that allows access to session and server information.
     * 
     * @var \TechDivision\Context\Context
     */
    protected $context;
    
    /**
     * The servlet session related with the requested session ID.
     * 
     * @var \TechDivision\ServletEngine\ServletSession
     */
    protected $session;
    
    /**
     * The response instance bound to this request.
     * 
     * @var \TechDivision\Servlet\Http\HttpServletResponse
     */
    protected $response;
    
    /**
     * Injects the context that allows access to session and
     * server information.
     * 
     * @param \TechDivision\Context\Context $context The request context instance
     * 
     * @return void
     */
    public function injectContext(Context $context)
    {
        $this->context = $context;
    }
    
    /**
     * Returns the context that allows access to session and
     * server information.
     * 
     * @return \TechDivision\Context\Context The request context
     */
    public function getContext()
    {
        return $this->context;
    }
    
    /**
     * Injects the servlet response bound to this request.
     * 
     * @param \TechDivision\Servlet\Http\HttpServletResponse $response The servlet respone instance
     * 
     * @return void
     */
    public function injectResponse(HttpServletResponse $response)
    {
        $this->response = $response;
    }
    
    /**
     * Returns the servlet response bound to this request.
     * 
     * @return \TechDivision\Servlet\Http\HttpServletResponse The response instance
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Returns an array with all request parameters.
     *
     * @param array $parameterMap The array with the request parameters
     * 
     * @return void
     */
    public function setParameterMap(array $parameterMap)
    {
        $this->parameterMap = $parameterMap;
    }

    /**
     * Returns an array with all request parameters.
     *
     * @return array The array with the request parameters
     */
    public function getParameterMap()
    {
        return $this->parameterMap;
    }

    /**
     * Return content
     *
     * @return string $content
     */
    public function getBodyStream()
    {
        return $this->bodyStream;
    }

    /**
     * Set protocol version
     *
     * @param string $version The http protocol version
     *
     * @return void
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Returns protocol version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Returns the parameter with the passed name if available or null
     * if the parameter not exists.
     *
     * @param string  $name   The name of the parameter to return
     * @param integer $filter The filter to use
     *
     * @return string|null
     * @todo Implement filter handling
     */
    public function getParameter($name, $filter = FILTER_SANITIZE_STRING)
    {
        $parameterMap = $this->getParameterMap();
        if (array_key_exists($name, $parameterMap)) {
            return filter_var($parameterMap[$name], $filter);
        }
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
        throw new \Exception('Method ' . __METHOD__ . ' not implemented yet');
    }

    /**
     * Returns the parts collection as array
     *
     * @return array A collection of HttpPart objects
     */
    public function getParts()
    {
        throw new \Exception('Method ' . __METHOD__ . ' not implemented yet');
    }
    
    /**
     * Sets the application context name (application name prefixed with a slash) for the actual request.
     * 
     * @param string $contextPath The application context name
     * 
     * @return void
     */
    public function setContextPath($contextPath)
    {
        $this->contextPath = $contextPath;
    }
    
    /**
     * Returns the application context name (application name prefixed with a slash) for the actual request.
     * 
     * @return string The application context name
     */
    public function getContextPath()
    {
        return $this->contextPath;
    }
    
    /**
     * Sets the path to the servlet used to handle this request.
     * 
     * @param string $servletPath The path to the servlet
     * 
     * @return void
     */
    public function setServletPath($servletPath)
    {
        $this->servletPath = $servletPath;
    }
    
    /**
     * Returns the path to the servlet used to handle this request.
     * 
     * @return string The relative path to the servlet
     */
    public function getServletPath()
    {
        return $this->servletPath;
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
        
        $requestedSessionId = $this->getRequestedSessionId();
        
        if ($this->session != null && $this->session->getId() === $requestedSessionId) {
            return $this->session;
        }
        
        $manager = $this->getContext()->getSessionManager();
        
        if ($manager == null) {
            return;
        }
        
        $this->session = $manager->find($requestedSessionId, $create);
        
        $this->session->setId($requestedSessionId);
        $this->session->injectRequest($this);
        $this->session->injectResponse($response = $this->getResponse());
        $this->session->start();
        
        $this->setRequestedSessionId($this->session->getId());
        
        return $this->session;
    }
    
    /**
     * Returns the absolute path info started from the context path.
     * 
     * @return string The absolute path info
     */
    public function getPathInfo()
    {
        return $this->pathInfo;
    }
    
    /**
     * Returns the absolute path info started from the context path.
     * 
     * @param string $pathInfo The absolute path info
     * 
     * @return void
     */
    public function setPathInfo($pathInfo)
    {
        $this->pathInfo = $pathInfo;
    }
    
    /**
     * Sets the query string of the actual request.
     * 
     * @param string $queryString The query string of the actual request
     * 
     * @return void
     */
    public function setQueryString($queryString)
    {
        $this->queryString = $queryString;
    }

    /**
     * Returns query string of the actual request.
     *
     * @return string|null The query string of the actual request
     */
    public function getQueryString()
    {
        return $this->queryString;
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
        return $this->headers[$key];
    }

    /**
     * Set headers data
     *
     * @param array $headers The headers array to set
     *
     * @return void
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }

    /**
     * Returns headers data
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Set request method
     *
     * @param string $method Request-Method
     *
     * @return void
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * Returns request method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set request uri
     *
     * @param string $uri The uri to set
     *
     * @return void
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * Returns request uri
     *
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }
    
    /**
     * Adds the passed cookie to this request.
     * 
     * @param \TechDivision\Servlet\Http\Cookie $cookie The cookie to add
     * 
     * @return void
     */
    public function addCookie(Cookie $cookie)
    {
        $this->cookies[$cookie->getName()] = $cookie;
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
        return array_key_exists($cookieName, $this->cookies);
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
        if ($this->hasCookie($cookieName)) {
            return $this->cookies[$cookieName];
        }
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
        $this->headers[$name] = $value;
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
        return isset($this->headers[$name]);
    }

    /**
     * Sets document root
     *
     * @param string $documentRoot The document root
     *
     * @return void
     */
    public function setDocumentRoot($documentRoot)
    {
        $this->documentRoot = $documentRoot;
    }

    /**
     * Returns the document root
     *
     * @return string
     */
    public function getDocumentRoot()
    {
        return $this->documentRoot;
    }
    
    /**
     * Set the requested session ID for this request.  This is normally called
     * by the HTTP Connector, when it parses the request headers.
     *
     * @param string $requestedSessionId The new session id
     * 
     * @return void
     */
    public function setRequestedSessionId($requestedSessionId)
    {
        $this->requestedSessionId = $requestedSessionId;
    }
    
    /**
     * Return the session identifier included in this request, if any.
     * 
     * @return string The session identifier included in this request
     */
    public function getRequestedSessionId()
    {
        return $this->requestedSessionId;
    }

    /**
     * Returns the script name
     *
     * @return string
     */
    public function getServerName()
    {
        return $this->getContext()->getServerVar('SERVER_NAME');
    }

    /**
     * Returns the server variables
     *
     * @return array The server variables
     */
    public function getServerVars()
    {
        return $this->getContext()->getServerVars();
    }

    /**
     * Returns the server variable with the requested name.
     *
     * @param string $name The name of the server variable to be returned
     *
     * @return mixed The requested server variable
     */
    public function getServerVar($name)
    {
        return $this->getContext()->getServerVar($name);
    }
}
