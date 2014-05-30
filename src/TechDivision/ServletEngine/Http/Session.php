<?php

/**
 * TechDivision\ServletEngine\Http\Session
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

use TechDivision\Servlet\Http\HttpSession;
use TechDivision\Storage\StackableStorage;
use TechDivision\Storage\GenericStackable;
use TechDivision\Servlet\Http\Cookie;
use TechDivision\Servlet\Http\HttpServletResponse;

/**
 * A modular session implementation based on the caching framework.
 *
 * You may access the currently active session in userland code. In order to do this,
 * inject TYPO3\Flow\Session\SessionInterface and NOT just TYPO3\Flow\Session\Session.
 * The former will be a unique instance (singleton) representing the current session
 * while the latter would be a completely new session instance!
 *
 * You can use the Session Manager for accessing sessions which are not currently
 * active.
 *
 * @category   Appserver
 * @package    TechDivision_ServletEngine
 * @subpackage Http
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */
class Session extends GenericStackable implements HttpSession
{

    /**
     * Initializes the session.
     *
     * @param mixed            $id         The session ID
     * @param string           $name       The session name
     * @param integer|DateTime $lifetime   Date and time after the session expires
     * @param integer|null     $maximumAge Number of seconds until the session expires
     * @param string|null      $domain     The host to which the user agent will send this cookie
     * @param string           $path       The path describing the scope of this cookie
     * @param boolean          $secure     If this cookie should only be sent through a "secure" channel by the user agent
     * @param boolean          $httpOnly   If this cookie should only be used through the HTTP protocol
     *
     * @return void
     */
    public function __construct($id, $name, $lifetime, $maximumAge, $domain, $path, $secure, $httpOnly)
    {

        // initialize the session
        $this->id = $id;
        $this->name = $name;
        $this->lifetime = $lifetime;
        $this->maximumAge = $maximumAge;
        $this->domain = $domain;
        $this->path = $path;
        $this->secure = $secure;
        $this->httpOnly = $httpOnly;

        // session has not been started yed
        $this->started = false;

        // initialize the storage for the session data
        $this->data = new StackableStorage();
    }

    /**
     * Tells if the session has been started already.
     *
     * @return boolean
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * Starts the session, if it has not been already started
     *
     * @return void
     */
    public function start()
    {

        // do nothing if the session has already been started
        if ($this->isStarted()) {
            return;
        }

        // set the session started
        $this->started = true;
    }

    /**
     * Creates and returns the session cookie to be added to the response.
     *
     * @param \TechDivision\Servlet\Http\ServletResponse The response that will be sent back to the client
     *
     * @return void
     */
    public function processResponse(HttpServletResponse $response)
    {

        // we need the session to be started
        if ($this->isStarted() === false) {
            return;
        }

        // someone else has already added the cookie
        if ($response->hasCookie($this->name)) {
            return;
        }

        // create a new cookie with the session values
        $cookie = new Cookie(
            $this->name,
            $this->id,
            $this->lifetime,
            $this->maximumAge,
            $this->domain,
            $this->path,
            $this->secure,
            $this->httpOnly
        );

        // add the cookie to the response
        $response->addCookie($cookie);
    }

    /**
     * Returns TRUE if there is a session that can be resumed.
     *
     * If a to-be-resumed session was inactive for too long, this function will
     * trigger the expiration of that session. An expired session cannot be resumed.
     *
     * NOTE that this method does a bit more than the name implies: Because the
     * session info data needs to be loaded, this method stores this data already
     * so it doesn't have to be loaded again once the session is being used.
     *
     * @return boolean
     */
    public function canBeResumed()
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    /**
     * Resumes an existing session, if any.
     *
     * @return integer If a session was resumed, the inactivity of since the last request is returned
     */
    public function resume()
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    /**
     * Returns the current session identifier
     *
     * @return string The current session identifier
     * @throws \TechDivision\Servlet\IllegalStateException
     */
    public function getId()
    {
        return $this->id;
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
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    /**
     * Returns the data associated with the given key.
     *
     * @param string $key An identifier for the content stored in the session.
     *
     * @return mixed The contents associated with the given key
     * @throws \TechDivision\Servlet\IllegalStateException
     */
    public function getData($key)
    {
        return $this->data->get($key);
    }

    /**
     * Returns TRUE if a session data entry $key is available.
     *
     * @param string $key Entry identifier of the session data
     *
     * @return boolean
     * @throws \TechDivision\Servlet\IllegalStateException
     */
    public function hasKey($key)
    {
        return $this->data->has($key);
    }

    /**
     * Stores the given data under the given key in the session
     *
     * @param string $key  The key under which the data should be stored
     * @param mixed  $data The data to be stored
     *
     * @return void
     * @throws \TechDivision\Servlet\IllegalStateException
     */
    public function putData($key, $data)
    {
        $this->data->set($key, $data);
    }

    /**
     * Returns the unix time stamp marking the last point in time this session has
     * been in use.
     *
     * For the current (local) session, this method will always return the current
     * time. For a remote session, the unix timestamp will be returned.
     *
     * @return integer UNIX timestamp
     * @throws \TechDivision\Servlet\IllegalStateException
     */
    public function getLastActivityTimestamp()
    {
    }

    /**
     * Tags this session with the given tag.
     *
     * Note that third-party libraries might also tag your session. Therefore it is
     * recommended to use namespaced tags such as "Acme-Demo-MySpecialTag".
     *
     * @param string $tag The tag – must match be a valid cache frontend tag
     *
     * @return void
     * @throws \TechDivision\Servlet\IllegalStateException
     */
    public function addTag($tag)
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    /**
     * Removes the specified tag from this session.
     *
     * @param string $tag The tag – must match be a valid cache frontend tag
     *
     * @return void
     * @throws \TechDivision\Servlet\IllegalStateException
     */
    public function removeTag($tag)
    {
    }

    /**
     * Returns the tags this session has been tagged with.
     *
     * @return array The tags or an empty array if there aren't any
     * @throws \TechDivision\Servlet\IllegalStateException
     */
    public function getTags()
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
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

    /**
     * Explicitly destroys all session data
     *
     * @return void
     * @throws \TechDivision\Servlet\IllegalStateException
     */
    public function destroy()
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    /**
     * Iterates over all existing sessions and removes their data if the inactivity
     * timeout was reached.
     *
     * @return void
     */
    public function collectGarbage()
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }
}
