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

use TechDivision\Storage\StorageInterface;
use TechDivision\Servlet\Http\Cookie;
use TechDivision\Servlet\Http\HttpSession;
use TechDivision\Servlet\Http\HttpServletRequest;
use TechDivision\Servlet\Http\HttpServletResponse;
use TechDivision\ServletEngine\SessionStorage;
use TechDivision\ServletEngine\DefaultSessionSettings;
use TechDivision\ServletEngine\SessionNotStartedException;
use TechDivision\ServletEngine\OperationNotSupportedException;
use TechDivision\ServletEngine\DataNotSerializableException;
use TechDivision\ServletEngine\InvalidRequestResponseException;
use TechDivision\ServletEngine\InvalidArgumentException;

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
class Session implements HttpSession
{

    /**
     * Prefix for all tags.
     * 
     * @var string
     */
    const TAG_PREFIX = 'customtag-';
    
    /**
     * The session cookie instance.
     * 
     * @var \TechDivision\Servlet\Http\Cookie
     */
    protected $sessionCookie;

    /**
     * The servlet request instance.
     * 
     * @var \TechDivision\Servlet\Http\HttpServletRequest
     */
    protected $request;

    /**
     * The servlet response instance.
     * 
     * @var \TechDivision\Servlet\Http\HttpServletResponse
     */
    protected $response;

    /**
     * Cache storage for this session.
     *
     * @var \TechDivision\ServletEngine\SessionStorage
     */
    protected $storage;

    /**
     * The session identifier
     *
     * @var string
     */
    protected $id;

    /**
     * If this session has been started
     *
     * @var boolean
     */
    protected $started = false;

    /**
     *
     * @var integer
     */
    protected $lastActivityTimestamp;

    /**
     *
     * @var array
     */
    protected $tags = array();

    /**
     *
     * @var integer
     */
    protected $now;
    
    /**
     * The session name to use.
     * 
     * @var string
     */
    protected $sessionName = DefaultSessionSettings::DEFAULT_SESSION_NAME;
    
    /**
     * The cookie domain set for the session.
     * 
     * @var string
     */
    protected $sessionCookieDomain = Cookie::LOCALHOST;
    
    /**
     * The cookie path set for the session.
     * 
     * @var string
     */
    protected $sessionCookiePath = DefaultSessionSettings::DEFAULT_SESSION_COOKIE_PATH;
    
    /**
     * The session cookie lifetime.
     * 
     * @var integer
     */
    protected $sessionCookieLifetime = 0;
    
    /**
     * The flag that the session cookie should only be set in a secure connection.
     * 
     * @var boolean
     */
    protected $sessionCookieSecure = false;
    
    /**
     * The flag if the session should set a Http only cookie.
     * 
     * @var boolean
     */
    protected $sessionCookieHttpOnly = false;
    
    /**
     * The probability the garbage collector will be invoked on the session.
     * 
     * @var float
     */
    protected $garbageCollectionProbability = 1.0;
    
    /**
     * The inactivity timeout until the session will be invalidated.
     * 
     * @var integer
     */
    protected $inactivityTimeout = 1440;

    /**
     * Constructs this session
     *
     * If $id is specified, this constructor will create a session
     * instance representing a remote session. In that case $storageIdentifier and
     * $lastActivityTimestamp are also required arguments.
     *
     * Session instances MUST NOT be created manually! They should be retrieved via
     * the Session Manager or through dependency injection (use SessionInterface!).
     * 
     * @param string|null  $id                    The public session identifier which is also used in the session cookie
     * @param integer|null $lastActivityTimestamp Unix timestamp of the last known activity for this session
     * @param array|null   $tags                  A list of tags set for this session
     */
    public function __construct($id = null, $lastActivityTimestamp = null, array $tags = array())
    {

        $this->now = time();
        $this->sessionCookieLifetime = time() + 86400;
        
        if ($id !== null) {
            $this->id = $id;
            $this->lastActivityTimestamp = $lastActivityTimestamp;
            $this->started = true;
            $this->tags = $tags;
        }
    }
    
    /**
     * Injects the Http request instance.
     *
     * @param \TechDivision\Servlet\Http\HttpServletRequest $request The request instance
     * 
     * @return void
     */
    public function injectRequest(HttpServletRequest $request)
    {
        $this->request = $request;
    }
    
    /**
     * Injects the Http response instance.
     * 
     * @param \TechDivision\Servlet\Http\HttpServletResponse $response The response instance
     * 
     * @return void
     */
    public function injectResponse(HttpServletResponse $response)
    {
        $this->response = $response;
    }

    /**
     * Injects the storage to persist session data.
     *
     * @param \TechDivision\Storage\StorageInterface $storage The session storage to use
     *
     * @return void
     */
    public function injectStorage(StorageInterface $storage)
    {
        $this->storage = $storage;
    }

    /**
     * Set's the unique session identifier.
     *
     * @param string $id The unique session identifier
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
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

        if ($this->started === true) {
            $this->initializeHttpAndCookie();
        } else {

           if ($this->id == null) {
               $this->id = $this->generateRandomString(32);
           }
           
           $this->sessionCookie = new Cookie(
                $this->getSessionName(),
                $this->id,
                $this->getSessionCookieLifetime(),
                null,
                $this->getSessionCookieDomain(),
                $this->getSessionCookiePath(),
                $this->getSessionCookieSecure(),
                $this->getSessionCookieHttpOnly()
            );
            
            $this->response->addCookie($this->sessionCookie);
            
            $this->lastActivityTimestamp = $this->now;
            $this->started = true;
            
            $this->writeSessionInfoCacheEntry();
        }
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

        $this->initializeHttpAndCookie();

        if ($this->sessionCookie === null || $this->request === null || $this->started === true) {
            return false;
        }

        $sessionInfo = $this->storage->get($this->sessionCookie->getValue());
        if ($sessionInfo === false) {
            return false;
        }

        $this->lastActivityTimestamp = $sessionInfo['lastActivityTimestamp'];
        $this->tags = $sessionInfo['tags'];
        return ! $this->autoExpire();
    }

    /**
     * Resumes an existing session, if any.
     *
     * @return integer If a session was resumed, the inactivity of since the last request is returned
     */
    public function resume()
    {
        
        if ($this->started === false && $this->canBeResumed()) {

            $this->id = $this->sessionCookie->getValue();
            $this->response->setCookie($this->sessionCookie);
            $this->started = true;

            $sessionObjects = $this->storage->get($this->id . md5(__CLASS__));

            if (is_array($sessionObjects)) {
                foreach ($sessionObjects as $object) {
                    if (method_exists($object, '__wakeup')) {
                        $object->__wakeup();
                    }
                }

            } else {
                $this->storage->set($this->id . md5(__CLASS__), array(), array($this->id), 0);
            }

            $lastActivitySecondsAgo = ($this->now - $this->lastActivityTimestamp);
            $this->lastActivityTimestamp = $this->now;
            return $lastActivitySecondsAgo;
        }
    }

    /**
     * Returns the current session identifier
     *
     * @return string The current session identifier
     * @throws \TechDivision\ServletEngine\SessionNotStartedException
     */
    public function getId()
    {
        if ($this->started !== true) {
            throw new SessionNotStartedException('Tried to retrieve the session identifier, but the session has not been started yet.');
        }
        return $this->id;
    }

    /**
     * Generates and propagates a new session ID and transfers all existing data
     * to the new session.
     *
     * @return string The new session ID
     * @throws \TechDivision\ServletEngine\SessionNotStartedException
     * @throws \TechDivision\ServletEngine\OperationNotSupportedException
     */
    public function renewId()
    {
        if ($this->started !== true) {
            throw new SessionNotStartedException('Tried to renew the session identifier, but the session has not been started yet.');
        }

        $this->removeSessionInfoCacheEntry($this->id);
        $this->id = $this->generateRandomString(32);
        $this->writeSessionInfoCacheEntry();

        $this->sessionCookie->setValue($this->id);
        return $this->id;
    }

    /**
     * Returns the data associated with the given key.
     *
     * @param string $key An identifier for the content stored in the session.
     *
     * @return mixed The contents associated with the given key
     * @throws \TechDivision\ServletEngine\SessionNotStartedException
     */
    public function getData($key)
    {
        if ($this->started !== true) {
            throw new SessionNotStartedException('Tried to get session data, but the session has not been started yet.');
        }
        return $this->storage->get($this->id . md5($key));
    }

    /**
     * Returns TRUE if a session data entry $key is available.
     *
     * @param string $key Entry identifier of the session data
     *
     * @return boolean
     * @throws \TechDivision\ServletEngine\SessionNotStartedException
     */
    public function hasKey($key)
    {
        if ($this->started !== true) {
            throw new SessionNotStartedException('Tried to check a session data entry, but the session has not been started yet.');
        }
        return $this->storage->has($this->id . md5($key));
    }

    /**
     * Stores the given data under the given key in the session
     *
     * @param string $key  The key under which the data should be stored
     * @param mixed  $data The data to be stored
     *
     * @return void
     * @throws \TechDivision\ServletEngine\DataNotSerializableException
     * @throws \TechDivision\ServletEngine\SessionNotStartedException
     */
    public function putData($key, $data)
    {
        if ($this->started !== true) {
            throw new SessionNotStartedException('Tried to create a session data entry, but the session has not been started yet.');
        }
        if (is_resource($data)) {
            throw new DataNotSerializableException('The given data cannot be stored in a session, because it is of type "' . gettype($data) . '".');
        }
        $this->storage->set(
            $this->id . md5($key),
            $data,
            array(
                $this->id
            ),
            0
        );
    }

    /**
     * Returns the unix time stamp marking the last point in time this session has
     * been in use.
     *
     * For the current (local) session, this method will always return the current
     * time. For a remote session, the unix timestamp will be returned.
     *
     * @return integer A UNIX timestamp
     * @throws \TechDivision\ServletEngine\SessionNotStartedException
     */
    public function getLastActivityTimestamp()
    {
        if ($this->started !== true) {
            throw new SessionNotStartedException('Tried to retrieve the last activity timestamp of a session which has not been started yet.');
        }
        return $this->lastActivityTimestamp;
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
     * @throws \TechDivision\ServletEngine\SessionNotStartedException
     * @throws \TechDivision\ServletEngine\InvalidArgumentException
     */
    public function addTag($tag)
    {
        if ($this->started !== true) {
            throw new SessionNotStartedException('Tried to tag a session which has not been started yet.');
        }
        if (! $this->storage->isValidTag($tag)) {
            throw new InvalidArgumentException(sprintf('The tag used for tagging session %s contained invalid characters. Make sure it matches this regular expression: "%s"', $this->id, FrontendInterface::PATTERN_TAG));
        }
        if (! in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }
    }

    /**
     * Removes the specified tag from this session.
     *
     * @param string $tag The tag – must match be a valid cache frontend tag
     *
     * @return void
     * @throws \TechDivision\ServletEngine\SessionNotStartedException
     */
    public function removeTag($tag)
    {
        if ($this->started !== true) {
            throw new SessionNotStartedException('Tried to tag a session which has not been started yet.');
        }
        $index = array_search($tag, $this->tags);
        if ($index !== false) {
            unset($this->tags[$index]);
        }
    }

    /**
     * Returns the tags this session has been tagged with.
     *
     * @return array The tags or an empty array if there aren't any
     * @throws \TechDivision\ServletEngine\SessionNotStartedException
     */
    public function getTags()
    {
        if ($this->started !== true) {
            throw new SessionNotStartedException('Tried to retrieve tags from a session which has not been started yet.');
        }
        return $this->tags;
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
        if ($this->started === true) {
            if ($this->storage->has($this->id)) {
                $this->writeSessionInfoCacheEntry();
            }
            $this->started = false;
            $decimals = strlen(strrchr($this->getGarbageCollectionProbability(), '.')) - 1;
            $factor = ($decimals > - 1) ? $decimals * 10 : 1;
            if (rand(0, 100 * $factor) <= ($this->getGarbageCollectionProbability() * $factor)) {
                $this->collectGarbage();
            }
        }
    }

    /**
     * Automatically expires the session if the user has been inactive for too long.
     *
     * @return boolean TRUE if the session expired, FALSE if not
     */
    protected function autoExpire()
    {
        $lastActivitySecondsAgo = $this->now - $this->lastActivityTimestamp;
        $expired = false;
        if ($this->getInactivityTimeout() !== 0 && $lastActivitySecondsAgo > $this->getInactivityTimeout()) {
            $this->started = true;
            $this->id = $this->sessionCookie->getValue();
            $this->destroy(sprintf('Session %s was inactive for %s seconds, more than the configured timeout of %s seconds.', $this->id, $lastActivitySecondsAgo, $this->getInactivityTimeout()));
            $expired = true;
        }
        return $expired;
    }

    /**
     * Initialize request, response and session cookie
     *
     * @return void
     */
    protected function initializeHttpAndCookie()
    {
        if ($this->request->hasCookie($this->getSessionName())) {
            $id = $this->request->getCookie($this->getSessionName())->getValue();
            $this->sessionCookie = new Cookie(
                $this->getSessionName(),
                $id,
                $this->getSessionCookieLifetime(),
                null,
                $this->getSessionCookieDomain(),
                $this->getSessionCookiePath(),
                $this->getSessionCookieSecure(),
                $this->getSessionCookieHttpOnly()
            );
        }
    }

    /**
     * Writes the cache entry containing information about the session, such as the
     * last activity time and the storage identifier.
     *
     * This function does not write the whole session _data_ into the storage cache,
     * but only the "head" cache entry containing meta information.
     *
     * The session cache entry is also tagged with "session", the session identifier
     * and any custom tags of this session, prefixed with TAG_PREFIX.
     *
     * @return void
     */
    protected function writeSessionInfoCacheEntry()
    {
        
        $sessionInfo = array(
            'lastActivityTimestamp' => $this->lastActivityTimestamp,
            'tags' => $this->tags
        );
        
        $tagsForCacheEntry = array_map(
            function ($tag) {
                return Session::TAG_PREFIX . $tag;
            },
            $this->tags
        );

        $tagsForCacheEntry[] = 'session';

        $this->storage->set($this->id, $sessionInfo, $tagsForCacheEntry, 0);
    }

    /**
     * Removes the session info cache entry for the specified session.
     *
     * Note that this function does only remove the "head" cache entry, not the
     * related data referred to by the storage identifier.
     *
     * @param string $id The sessions's id
     *
     * @return void
     */
    protected function removeSessionInfoCacheEntry($id)
    {
        $this->storage->remove($id);
    }

    /**
     * Explicitly writes and closes the session
     *
     * @return void
     */
    public function close()
    {
        $this->shutdownObject();
    }

    /**
     * Explicitly destroys all session data
     *
     * @return void
     * @throws \TechDivision\ServletEngine\SessionNotStartedException
     */
    public function destroy()
    {
        if ($this->started !== true) {
            throw new SessionNotStartedException('Tried to destroy a session which has not been started yet.');
        }
        if ($this->response->hasCookie($this->getSessionName()) === false) {
            $this->response->addCookie($this->sessionCookie);
        }
        $this->sessionCookie->expire();
        $this->removeSessionInfoCacheEntry($this->id);
        $this->storage->flushByTag($this->id);
        $this->started = false;
        $this->id = null;
        $this->tags = array();
    }

    /**
     * Iterates over all existing sessions and removes their data if the inactivity
     * timeout was reached.
     *
     * @return void
     */
    public function collectGarbage()
    {
        $sessionRemovalCount = 0;
        if ($this->getInactivityTimeout() !== 0) {
            foreach ($this->storage->getByTag('session') as $sessionInfo) {
                $lastActivitySecondsAgo = $this->now - $sessionInfo['lastActivityTimestamp'];
                if ($lastActivitySecondsAgo > $this->getInactivityTimeout()) {
                    $this->storage->flushByTag($this->id);
                    $sessionRemovalCount ++;
                }
            }
        }
    }
    
    /**
     * Creates a random string with the passed lenght.
     * 
     * @param integer $length The string lenght to generate
     * 
     * @return string The random string
     */
    protected function generateRandomString($length = 32)
    {
        
        // prepare an array with the chars used to create a random string
        $letters = str_split('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz');
        
        // create and return the random string
        $bytes = '';
        foreach (range(1, $length) as $i) {
            $bytes = $letters[mt_rand(0, sizeof($letters) - 1)] . $bytes;
        }
        return $bytes;
    }
    
    /**
     * Returns the session name to use.
     * 
     * @return string The session name
     */
    public function getSessionName()
    {
        return $this->sessionName;
    }
    
    /**
     * Returns the session cookie lifetime.
     * 
     * @return integer
     */
    public function getSessionCookieLifetime()
    {
        return $this->sessionCookieLifetime;
    }
    
    /**
     * Returns the cookie domain set for the session.
     * 
     * @return string
     */
    public function getSessionCookieDomain()
    {
        return $this->sessionCookieDomain;
    }
    
    /**
     * Returns the cookie path set for the session.
     * 
     * @return string
     */
    public function getSessionCookiePath()
    {
        return $this->sessionCookiePath;
    }
    
    /**
     * Returns the flag that the session cookie should only be set in a secure connection.
     * 
     * @return boolean TRUE if a secure cookie should be set, else FALSE
     */
    public function getSessionCookieSecure()
    {
        return $this->sessionCookieSecure;
    }
    
    /**
     * Returns the flag if the session should set a Http only cookie.
     * 
     * @return boolean TRUE if a Http only cookie should be used
     */
    public function getSessionCookieHttpOnly()
    {
        return $this->sessionCookieHttpOnly;
    }
    
    /**
     * Returns the probability the garbage collector will be invoked on the session.
     * 
     * @return float The garbage collector probability
     */
    public function getGarbageCollectionProbability()
    {
        return $this->garbageCollectionProbability;
    }
    
    /**
     * Returns the inactivity timeout until the session will be invalidated.
     * 
     * @return integer The inactivity timeout in seconds
     */
    public function getInactivityTimeout()
    {
        return $this->inactivityTimeout;
    }

    /**
     * Returns the session name to use.
     *
     * @param string $sessionName The session name
     * 
     * @return void
     */
    public function setSessionName($sessionName)
    {
        $this->sessionName = $sessionName;
    }
    
    /**
     * Returns the session cookie lifetime.
     *
     * @param integer $sessionCookieLifetime session cookie lifetime
     * 
     * @return void
     */
    public function setSessionCookieLifetime($sessionCookieLifetime)
    {
        $this->sessionCookieLifetime = $sessionCookieLifetime;
    }
    
    /**
     * Returns the cookie domain set for the session.
     *
     * @param string $sessionCookieDomain The cookie domain set for the session
     * 
     * @return void
     */
    public function setSessionCookieDomain($sessionCookieDomain)
    {
        $this->sessionCookieDomain = $sessionCookieDomain;
    }
    
    /**
     * Returns the cookie path set for the session.
     *
     * @param string $sessionCookiePath The cookie path set for the session
     * 
     * @return void
     */
    public function setSessionCookiePath($sessionCookiePath)
    {
        $this->sessionCookiePath = $sessionCookiePath;
    }
    
    /**
     * Returns the flag that the session cookie should only be set in a secure connection.
     *
     * @param boolean $sessionCookieSecure TRUE if a secure cookie should be set, else FALSE
     * 
     * @return void
     */
    public function setSessionCookieSecure($sessionCookieSecure)
    {
        $this->sessionCookieSecure = $sessionCookieSecure;
    }
    
    /**
     * Returns the flag if the session should set a Http only cookie.
     *
     * @param boolean $sessionCookieHttpOnly TRUE if a Http only cookie should be used
     * 
     * @return void
     */
    public function setSessionCookieHttpOnly($sessionCookieHttpOnly)
    {
        $this->sessionCookieHttpOnly = $sessionCookieHttpOnly;
    }
    
    /**
     * Returns the probability the garbage collector will be invoked on the session.
     *
     * @param float $garbageCollectionProbability The garbage collector probability
     * 
     * @return void
     */
    public function setGarbageCollectionProbability($garbageCollectionProbability)
    {
        $this->garbageCollectionProbability = $garbageCollectionProbability;
    }
    
    /**
     * Returns the inactivity timeout until the session will be invalidated.
     *
     * @param integer $inactivityTimeout The inactivity timeout in seconds
     * 
     * @return void
     */
    public function setInactivityTimeout($inactivityTimeout)
    {
        $this->inactivityTimeout = $inactivityTimeout;
    }
}
