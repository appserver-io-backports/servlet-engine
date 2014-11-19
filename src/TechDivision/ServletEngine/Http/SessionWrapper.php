<?php

/**
 * TechDivision\ServletEngine\Http\SessionWrapper
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

use TechDivision\Http\HttpCookie;
use TechDivision\Servlet\SessionUtils;
use TechDivision\Servlet\ServletSession;
use TechDivision\Servlet\Http\HttpServletRequest;
use TechDivision\Servlet\Http\HttpSessionWrapper;
use TechDivision\ServletEngine\SessionManager;

/**
 * A wrapper to simplify session handling.
 *
 * @category   Appserver
 * @package    TechDivision_ServletEngine
 * @subpackage Http
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */
class SessionWrapper extends HttpSessionWrapper
{

    /**
     * The request instance we're working on.
     *
     * @var \TechDivision\Servlet\Http\HttpServletRequest
     */
    protected $request;

    /**
     * Injects the request instance.
     *
     * @param \TechDivision\Servlet\Http\HttpServletRequest $request The request instance we're working on
     *
     * @return void
     */
    public function injectRequest(HttpServletRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Returns the request instance we're working on.
     *
     * @return \TechDivision\Servlet\Http\HttpServletRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Returns the response instance we're working on.
     *
     * @return \TechDivision\Servlet\Http\HttpServletResponse
     */
    public function getResponse()
    {
        return $this->request->getResponse();
    }

    /**
     * Creates and returns the session cookie to be added to the response.
     *
     * @return void
     */
    public function start()
    {

        // we need the session to be started
        if ($this->isStarted()) {
            return;
        }

        // create a new cookie with the session values
        $cookie = new HttpCookie(
            $this->getName(),
            $this->getId(),
            $this->getLifetime(),
            $this->getMaximumAge(),
            $this->getDomain(),
            $this->getPath(),
            $this->isSecure(),
            $this->isHttpOnly()
        );

        // start the session and set the started flag
        $this->getSession()->start();

        // add the cookie to the response
        $this->getRequest()->setRequestedSessionId($this->getId());
        $this->getResponse()->addCookie($cookie);
    }

    /**
     * Explicitly destroys all session data and adds a cookie to the
     * response that invalidates the session in the browser.
     *
     * @param string $reason The reason why the session has been destroyed
     *
     * @return void
     */
    public function destroy($reason)
    {

        // check if the session has already been destroyed
        if ($this->getId() != null) {

            // create a new cookie with the session values
            $cookie = new HttpCookie(
                $this->getName(),
                $this->getId(),
                $this->getLifetime(),
                $this->getMaximumAge(),
                $this->getDomain(),
                $this->getPath(),
                $this->isSecure(),
                $this->isHttpOnly()
            );

            // let the cookie expire
            $cookie->expire();

            // and add it to the response
            $this->getResponse()->addCookie($cookie);
        }

        // destroy the sessions data
        parent::destroy($reason);
    }

    /**
     * Generates and propagates a new session ID and transfers all existing data
     * to the new session.
     *
     * @return string The new session ID
     * @throws \TechDivision\Servlet\IllegalStateException
     */
    public function renewId()
    {

        // create a new session ID
        $this->setId(SessionUtils::generateRandomString());

        // load the session manager
        $sessionManager = $this->getContext()->search('SessionManager');

        // attach this session with the new ID
        $sessionManager->attach($this->getSession());

        // create a new cookie with the session values
        $cookie = new HttpCookie(
            $this->getName(),
            $this->getId(),
            $this->getLifetime(),
            $this->getMaximumAge(),
            $this->getDomain(),
            $this->getPath(),
            $this->isSecure(),
            $this->isHttpOnly()
        );

        // add the cookie to the response
        $this->getRequest()->setRequestedSessionId($this->getId());
        $this->getResponse()->addCookie($cookie);

        // return the new session ID
        return $this->getId();
    }

    /**
     * Shuts down this session
     *
     * This method must not be called manually – it is invoked by Flow's object
     * management.
     *
     * @return void
     */
    public function shutdownObject()
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    /**
     * Explicitly writes and closes the session
     *
     * @return void
     */
    public function close()
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }
}
