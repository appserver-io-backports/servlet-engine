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
use TechDivision\Http\HttpCookieInterface;
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Servlet\SessionUtils;
use TechDivision\Servlet\Http\HttpSession;
use TechDivision\Servlet\Http\HttpSessionWrapper;
use TechDivision\Servlet\Http\HttpServletRequest;
use TechDivision\Servlet\Http\HttpServletResponse;
use TechDivision\ServletEngine\SessionManager;
use TechDivision\Server\Dictionaries\ServerVars;
use TechDivision\ApplicationServer\Interfaces\ApplicationInterface;

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
     * The body stream, a string because we can't serialize memory stream here.
     *
     * @var string
     */
    protected $bodyStream;

    /**
     * The ID of requested session.
     *
     * @var string
     */
    protected $requestedSessionId;

    /**
     * The name of requested session.
     *
     * @var string
     */
    protected $requestedSessionName;

    /**
     * Path info.
     *
     * @var string
     */
    protected $pathInfo;

    /**
     * The path to the servlet used to handle the request.
     *
     * @var string
     */
    protected $servletPath;

    /**
     * The Http request instance.
     *
     * @var \TechDivision\Http\HttpRequestInteface
     */
    protected $httpRequest;

    /**
     * The response instance bound to this request.
     *
     * @var \TechDivision\Servlet\Http\HttpServletResponse
     */
    protected $response;

    /**
     * The server variables.
     *
     * @var array
     */
    protected $serverVars = array();

    /**
     * Flag that the request has been dispatched.
     *
     * @var boolean
     */
    protected $dispatched = false;

    /**
     * The request context that handles the request.
     *
     * @var \TechDivision\Context\Context
     */
    protected $context;

    /**
     * The array with the file parts.
     *
     * @var array
     */
    protected $parts = array();

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
     * Injects the server variables.
     *
     * @param \TechDivision\Storage\GenericStackable $serverVars The server variables
     *
     * @return void
     */
    public function injectServerVars($serverVars)
    {
        $this->serverVars = $serverVars;
    }

    /**
     * Injects the Http request instance.
     *
     * @param \TechDivision\Http\HttpRequestInterface $httpRequest The Http request instance
     *
     * @return void
     */
    public function injectHttpRequest(HttpRequestInterface $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }

    /**
     * Returns the Http request instance.
     *
     * @return \TechDivision\Http\HttpRequestInterface The Http request instance
     */
    public function getHttpRequest()
    {
        return $this->httpRequest;
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
     * Returns the context that allows access to session and
     * server information.
     *
     * @return \TechDivision\ServletEngine\Http\RequestContext The request context
     */
    public function getRequestHandler()
    {
        return $this->requestHandler;
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
        $this->getHttpRequest()->setParams($parameterMap);
    }

    /**
     * Returns an array with all request parameters.
     *
     * @return array The array with the request parameters
     */
    public function getParameterMap()
    {
        return $this->getHttpRequest()->getParams();
    }

    /**
     * Return content
     *
     * @return string $content
     */
    public function getBodyContent()
    {
        return $this->getBodyStream();
    }

    /**
     * Returns the body stream as a resource.
     *
     * @return resource The body stream
     */
    public function getBodyStream()
    {
        return $this->bodyStream;
    }

    /**
     * Resetss the stream resource pointing to body content.
     *
     * @param resource $bodyStream The body content stream resource
     *
     * @return void
     */
    public function setBodyStream($bodyStream)
    {
        $this->bodyStream = $bodyStream;
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
        $this->getHttpRequest()->setVersion($version);
    }

    /**
     * Returns protocol version
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->getHttpRequest()->getVersion();
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
        if (array_key_exists($name, $this->parts)) {
            return $this->parts[$name];
        }
    }

    /**
     * Returns the parts collection as array
     *
     * @return array A collection of HttpPart objects
     */
    public function getParts()
    {
        return $this->parts;
    }

    /**
     * Adds a part to the parts collection.
     *
     * @param \TechDivision\ServletEngine\Http\Part $part A form part object
     * @param string                                $name A manually defined name
     *
     * @return void
     */
    public function addPart(Part $part, $name = null)
    {
        if ($name == null) {
            $name = $part->getName();
        }
        $this->parts[$name] = $part;
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

        // if no session has already been load, initialize the session manager
        $manager = $this->getContext()->getManager(SessionManager::IDENTIFIER);

        // if no session manager was found, we don't support sessions
        if ($manager == null) {
            return;
        }

        // if we can't find a requested session name, we try to load the default session cookie
        if ($this->getRequestedSessionName() == null) {
            $this->setRequestedSessionName($manager->getSessionSettings()->getSessionName());
        }

        // load the requested session ID and name
        $sessionName = $this->getRequestedSessionName();
        $id = $this->getRequestedSessionId();

        // try to load session ID from session cookie of request/response
        if ($id == null && ($cookie = $this->getResponse()->getCookie($sessionName)) != null) {
            $this->setRequestedSessionId($cookie->getValue());
        } elseif ($id == null && ($cookie = $this->getCookie($sessionName)) != null) {
            $this->setRequestedSessionId($cookie->getValue());
        }

        // find or create a new session (if flag has been set)
        $session = $manager->find($this->getRequestedSessionId());

        // if we can't find a session or session has been expired and we want to create a new one
        if ($session == null && $create === true) {

            // check if a session ID has been specified
            if ($id == null) { // if not, generate a unique one
                $id = SessionUtils::generateRandomString();
            }

            // create a new session and register ID in request
            $session = $manager->create($id, $sessionName);
        }

        // if we can't find a session nor we've created one, so we return nothing!
        if ($session == null) {
            return;
        }

        // initialize the session wrapper
        $wrapper = new HttpSessionWrapper();
        $wrapper->injectSession($session);
        $wrapper->injectRequest($this);

        // return the found session
        return $wrapper;
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
     * Adds the passed cookie to this request.
     *
     * @param \TechDivision\Http\HttpCookieInterface $cookie The cookie to add
     *
     * @return void
     */
    public function addCookie(HttpCookieInterface $cookie)
    {
        $this->getHttpRequest()->addCookie($cookie);
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
        return $this->getHttpRequest()->hasCookie($cookieName);
    }

    /**
     * Returns the value of the cookie with the passed name.
     *
     * @param string $cookieName The name of the cookie to return
     *
     * @return \TechDivision\Http\HttpCookieInterface The cookie instance
     */
    public function getCookie($cookieName)
    {
        return $this->getHttpRequest()->getCookie($cookieName);
    }

    /**
     * Returns header info by given name
     *
     * @param string $name The header key to name
     *
     * @return string|null
     */
    public function getHeader($name)
    {
        return $this->getHttpRequest()->getHeader($name);
    }

    /**
     * Set headers data
     *
     * @param array $headers The headers array to set
     *
     * @return void
     */
    public function setHeaders(array $headers)
    {
        $this->getHttpRequest()->setHeaders($headers);
    }

    /**
     * Returns headers data
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->getHttpRequest()->getHeaders();
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
        $this->getHttpRequest()->addHeader($name, $value);
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
        return $this->getHttpRequest()->hasHeader($name);
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
     * Set the requested session name for this request.
     *
     * @param string $requestedSessionName The new session name
     *
     * @return void
     */
    public function setRequestedSessionName($requestedSessionName)
    {
        $this->requestedSessionName = $requestedSessionName;
    }

    /**
     * Return the session name included in this request, if any.
     *
     * @return string The session name included in this request
     */
    public function getRequestedSessionName()
    {
        return $this->requestedSessionName;
    }

    /**
     * Sets the flag to mark the request dispatched.
     *
     * @param boolean $dispatched TRUE if the request has already been dispatched, else FALSE
     *
     * @return void
     */
    public function setDispatched($dispatched = true)
    {
        $this->dispatched = $dispatched;
    }

    /**
     * Sets the flag that shows if the request has already been dispatched.
     *
     * @return boolean TRUE if the request has already been dispatched, else FALSE
     */
    public function isDispatched()
    {
        return $this->dispatched;
    }

    /**
     * Returns the script name
     *
     * @return string
     */
    public function getServerName()
    {
        return $this->getServerVar(ServerVars::SERVER_NAME);
    }

    /**
     * Returns query string of the actual request.
     *
     * @return string|null The query string of the actual request
     */
    public function getQueryString()
    {
        return $this->getServerVar(ServerVars::QUERY_STRING);
    }

    /**
     * Returns request uri
     *
     * @return string
     */
    public function getUri()
    {
        return $this->getServerVar(ServerVars::X_REQUEST_URI);
    }

    /**
     * Sets the URI.
     *
     * @param string $uri The uri
     *
     * @return void
     */
    public function setUri($uri)
    {
        $this->getHttpRequest()->setUri($uri);
    }

    /**
     * Returns request method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->getServerVar(ServerVars::REQUEST_METHOD);
    }

    /**
     * Sets the method to be performed on the resource identified by the
     * Request-URI.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * @param string $method Case-insensitive method
     *
     * @return void
     */
    public function setMethod($method)
    {
        $this->getHttpRequest()->setMethod($method);
    }

    /**
     * Returns the array with the server variables.
     *
     * @return \TechDivision\Storage\GenericStackable The array with the server variables
     */
    public function getServerVars()
    {
        return $this->serverVars;
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
        if (array_key_exists($name, $serverVars = $this->getServerVars())) {
            return $serverVars[$name];
        }
    }
}
