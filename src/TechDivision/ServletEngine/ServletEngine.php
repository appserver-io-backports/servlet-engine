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
use TechDivision\Http\HttpRequestInterface;
use TechDivision\Http\HttpResponseInterface;
use TechDivision\Http\HttpResponseStates;
use TechDivision\Storage\GenericStackable;
use TechDivision\Servlet\Http\Cookie;
use TechDivision\Servlet\ServletRequest;
use TechDivision\Servlet\ServletResponse;
use TechDivision\Servlet\Http\HttpServletRequest;
use TechDivision\Servlet\Http\HttpServletResponse;
use TechDivision\Server\Dictionaries\ModuleHooks;
use TechDivision\Server\Dictionaries\ServerVars;
use TechDivision\Server\Interfaces\ModuleInterface;
use TechDivision\Server\Interfaces\ServerContextInterface;
use TechDivision\ServletEngine\Http\Session;
use TechDivision\ServletEngine\Http\Request;
use TechDivision\ServletEngine\Http\Response;
use TechDivision\ServletEngine\Http\HttpRequestContext;
use TechDivision\ServletEngine\Authentication\AuthenticationValve;
use TechDivision\ApplicationServer\Interfaces\ContextInterface;
use TechDivision\ApplicationServer\Interfaces\ContainerInterface;

/**
 * A servlet engine implementation.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2013 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class ServletEngine extends GenericStackable implements ModuleInterface
{

    /**
     * The unique module name in the web server context.
     *
     * @var string
     */
    const MODULE_NAME = 'servlet';

    /**
     * Initialize the module.
     *
     * @return void
     */
    public function __construct()
    {

        /**
         * Storage for the servlet engines valves that handles the request.
         *
         * @var \TechDivision\Storage\GenericStackable
         */
        $this->valves = new GenericStackable();

        /**
         * Storage handlers registered in the web server.
         *
         * @var \TechDivision\Storage\GenericStackable
         */
        $this->handlers = new GenericStackable();

        /**
         * Storage with the available applications.
         *
         * @var \TechDivision\Storage\GenericStackable
         */
        $this->applications = new GenericStackable();

        /**
         * Storage with the available applications.
         *
         * @var \TechDivision\Storage\GenericStackable
         */
        $this->dependencies = new GenericStackable();

        /**
         * Storage with the registered virtual hosts.
         *
         * @var \TechDivision\Storage\GenericStackable
         */
        $this->virtualHosts = new GenericStackable();

        /**
         * Storage with the registered request handlers.
         *
         * @var \TechDivision\Storage\GenericStackable
         */
        $this->requestHandlers = new GenericStackable();

        /**
         * Storage with the thread ID's of the request handlers actually handling a request.
         *
         * @var \TechDivision\Storage\GenericStackable
         */
        $this->workingRequestHandlers = new GenericStackable();
    }

    /**
     * Returns an array of module names which should be executed first.
     *
     * @return \TechDivision\Storage\GenericStackable The module names this module depends on
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * Returns the module name.
     *
     * @return string The module name
     */
    public function getModuleName()
    {
        return ServletEngine::MODULE_NAME;
    }

    /**
     * Initializes the module.
     *
     * @param \TechDivision\Server\Interfaces\ServerContextInterface $serverContext The servers context instance
     *
     * @return void
     * @throws \TechDivision\Server\Exceptions\ModuleException
     */
    public function init(ServerContextInterface $serverContext)
    {
        try {

            // set the servlet context
            $this->serverContext = $serverContext;

            // initialize the valves that handles the requests
            $this->valves[] = new AuthenticationValve();
            $this->valves[] = new ServletValve();

            // load the document root and the web servers virtual host configuration
            $documentRoot = $serverContext->getServerConfig()->getDocumentRoot();

            // prepare the handlers
            foreach ($serverContext->getServerConfig()->getHandlers() as $extension => $handler) {
                $this->handlers[$extension] = new Handler($handler['name']);
            }

            // prepare the virtual host configurations
            foreach ($serverContext->getServerConfig()->getVirtualHosts() as $domain => $virtualHost) {

                // prepare the applications base directory
                $appBase = str_replace($documentRoot, '', $virtualHost['params']['documentRoot']);

                // append the virtual host to the array
                $this->virtualHosts[] = new VirtualHost($domain, $appBase);
            }

            // iterate over a applications vhost/alias configuration
            foreach ($serverContext->getContainer()->getApplications() as $application) {

                // iterate over the virtual hosts
                foreach ($this->virtualHosts as $virtualHost) {

                    // check if the virtual host match the application
                    if ($virtualHost->match($application)) {

                        // bind the virtual host to the application
                        $application->addVirtualHost($virtualHost);

                        // add the application to the internal array
                        $this->applications['/^' . $virtualHost->getName() . '\/(([a-z0-9+\$_-]\.?)+)*\/?/'] = $application;
                    }
                }

                // finally APPEND a wildcard pattern for each application to the patterns array
                $this->applications['/^[a-z0-9-.]*\/' . $application->getName() . '\/(([a-z0-9+\$_-]\.?)+)*\/?/'] = $application;

                // we want to prepare an request for each application and each worker
                $this->requestHandlers['/' . $application->getName()] = new GenericStackable();
                for ($i = 0; $i < 4; $i++) {
                    $this->requestHandlers['/' . $application->getName()][$i] = new RequestHandler($application);
                }
            }

        } catch (\Exception $e) {
            throw new ModuleException($e);
        }
    }

    /**
     * Prepares the module for upcoming request in specific context
     *
     * @return bool
     * @throws \TechDivision\Server\Exceptions\ModuleException
     */
    public function prepare()
    {
    }

    /**
     * Process the servlet engine.
     *
     * @param \TechDivision\Http\HttpRequestInterface  $request  The request object
     * @param \TechDivision\Http\HttpResponseInterface $response The response object
     * @param int                                      $hook     The current hook to process logic for
     *
     * @return bool
     * @throws \TechDivision\Server\Exceptions\ModuleException
     */
    public function process(HttpRequestInterface $request, HttpResponseInterface $response, $hook)
    {

        try {

            // if false hook is comming do nothing
            if (ModuleHooks::REQUEST_POST !== $hook) {
                return;
            }

            // make server context local
            $serverContext = $this->getServerContext();

            // check if we are the handler that has to process this request
            if ($serverContext->getServerVar(ServerVars::SERVER_HANDLER) !== $this->getModuleName()) {
                return;
            }

            // intialize servlet session, request + response
            $servletRequest = new Request();
            $servletRequest->injectHttpRequest($request);
            $servletRequest->injectServerVars($serverContext->getServerVars());

            // prepare the servlet request
            $this->prepareServletRequest($servletRequest);

            // initialize the servlet response with the Http response values
            $servletResponse = new Response();
            $servletResponse->injectHttpResponse($response);
            $servletRequest->injectResponse($servletResponse);

            // load a NOT working request handler from the pool
            $this->requestHandlerFromPool($servletRequest);

            // process the valves
            foreach ($this->getValves() as $valve) {
                $valve->invoke($servletRequest, $servletResponse);
                if ($servletRequest->isDispatched() === true) {
                    break;
                }
            }

            // re-attach the request handler to the pool
            $this->requestHandlerToPool($servletRequest);

            // append the content to the body stream
            $response->appendBodyStream($servletResponse->getBodyStream());

            // transform the servlet response cookies into Http headers
            foreach ($servletResponse->getCookies() as $cookie) {
                $response->addHeader(HttpProtocol::HEADER_SET_COOKIE, $cookie->__toString());
            }

            // set response state to be dispatched after this without calling other modules process
            $response->setState(HttpResponseStates::DISPATCH);

        } catch (ModuleException $me) {
            throw $me;
        } catch (\Exception $e) {
            throw new ModuleException($e, 500);
        }
    }

    /**
     * Tries to find a request handler that matches the actual request and injects it into the request.
     *
     * @param \TechDivision\Servlet\Http\HttpServletRequest $servletRequest The request instance to we have to inject a request handler
     *
     * @return void
     */
    protected function requestHandlerFromPool(HttpServletRequest $servletRequest)
    {

        // we search for a request handler as long $handlerFound is empty
        while ($handlerFound == null) {

            // iterate over all request handlers for the request we has to handle
            foreach ($this->requestHandlers[$servletRequest->getContextPath()] as $i => $requestHandler) {

                // if we've found a NOT working request handler, we stop
                if (!isset($this->workingRequestHandlers[$threadId = $requestHandler->getThreadId()])) {

                    // mark the request handler working and initialize the found one
                    $this->workingRequestHandlers[$threadId] = true;
                    $handlerFound = $requestHandler;
                    break;
                }
            }

            // we try to avoid burning the the CPU
            usleep(10000);
        }

        // inject the found request handler into the servlet request
        $servletRequest->injectRequestHandler($handlerFound);
    }

    /**
     * After a request has been processed by the injected request handler we remove
     * the thread ID of the request handler from the array with the working handlers.
     *
     * @param \TechDivision\Servlet\Http\HttpServletRequest $servletRequest The request instance we want the request handler to freed
     *
     * @return void
     */
    protected function requestHandlerToPool(HttpServletRequest $servletRequest)
    {
        unset($this->workingRequestHandlers[$servletRequest->getRequestHandler()->getThreadId()]);
    }

    /**
     * Tries to find an application that matches the passed request.
     *
     * @param \TechDivision\Servlet\Http\HttpServletRequest $servletRequest The request instance to locate the application for
     *
     * @return array The application info that matches the request
     * @throws \TechDivision\ServletEngine\BadRequestException Is thrown if no application matches the request
     */
    protected function prepareServletRequest(HttpServletRequest $servletRequest)
    {

        // transform the cookie headers into real servlet cookies
        if ($servletRequest->hasHeader(HttpProtocol::HEADER_COOKIE)) {

            // explode the cookie headers
            $cookieHeaders = explode('; ', $servletRequest->getHeader(HttpProtocol::HEADER_COOKIE));

            // create real cookie for each cookie key/value pair
            foreach ($cookieHeaders as $cookieHeader) {
                $servletRequest->addCookie(Cookie::createFromRawSetCookieHeader($cookieHeader));
            }
        }

        // load the request URI and query string
        $uri = $servletRequest->getUri();
        $queryString = $servletRequest->getQueryString();

        // get uri without querystring
        $uriWithoutQueryString = str_replace('?' . $queryString, '', $uri);

        // initialize the path information and the directory to start with
        list ($dirname, $basename, $extension) = array_values(pathinfo($uriWithoutQueryString));

        // make the registered handlers local
        $handlers = $this->getHandlers();

        do { // descent the directory structure down to find the (almost virtual) servlet file

            // bingo we found a (again: almost virtual) servlet file
            if (array_key_exists(".$extension", $handlers) && $handlers[".$extension"]->getName() === $this->getModuleName()) {

                // prepare the servlet path
                if ($dirname === '/') {
                    $servletPath = '/' . $basename;
                } else {
                    $servletPath = $dirname . '/' . $basename;
                }

                // we set the basename, because this is the servlet path
                $servletRequest->setServletPath($servletPath);

                // we set the path info, what is the request URI with stripped dir- and basename
                $servletRequest->setPathInfo(
                    $pathInfo = str_replace(
                        $servletPath,
                        '',
                        $uriWithoutQueryString
                    )
                );

                // we've found what we were looking for, so break here
                break;
            }

            // descendent down the directory tree
            list ($dirname, $basename, $extension) = array_values(pathinfo($dirname));

        } while ($dirname !== false); // stop until we reached the root of the URI

        // explode host and port from the host header
        list ($host, $port) = explode(':', $servletRequest->getHeader(HttpProtocol::HEADER_HOST));

        // prepare the URI to be matched
        $url =  $host . $uri;

        // try to find the application by match it one of the prepared patterns
        foreach ($this->getApplications() as $pattern => $application) {

            // try to match a registered application with the passed request
            if (preg_match($pattern, $url) === 1) {

                // prepare and set the applications context path
                $servletRequest->setContextPath($contextPath = '/' . $application->getName());

                // prepare the path information depending if we're in a vhost or not
                if ($application->isVhostOf($host) === false) {
                    $servletRequest->setServletPath(str_replace($contextPath, '', $servletRequest->getServletPath()));
                }

                // return, because request has been prepared successfully
                return;
            }
        }

        // if not throw a bad request exception
        throw new BadRequestException(sprintf('Can\'t find application for URI %s', $uri));
    }

    /**
     * Returns the server context instance.
     *
     * @return \TechDivision\Server\ServerContext The actual server context instance
     */
    public function getServerContext()
    {
        return $this->serverContext;
    }

    /**
     * Returns the initialized applications.
     *
     * @return \TechDivision\Storage\GenericStackable The initialized application instances
     */
    public function getApplications()
    {
        return $this->applications;
    }

    /**
     * Returns the initialized valves.
     *
     * @return \TechDivision\Storage\GenericStackable The initialized valves
     */
    public function getValves()
    {
        return $this->valves;
    }

    /**
     * Returns the initialized web server handlers.
     *
     * @return \TechDivision\Storage\GenericStackable The initialized web server handlers
     */
    public function getHandlers()
    {
        return $this->handlers;
    }
}
