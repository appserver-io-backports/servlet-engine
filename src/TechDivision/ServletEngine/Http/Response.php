<?php

/**
 * TechDivision\ServletEngine\Http\Response
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

use TechDivision\Http\HttpResponseInterface;
use TechDivision\Http\HttpProtocol;
use TechDivision\Servlet\Http\Cookie;
use TechDivision\Servlet\Http\HttpServletResponse;

/**
 * A servlet request implementation.
 *
 * @category   Appserver
 * @package    TechDivision_ServletEngine
 * @subpackage Http
 * @author     Tim Wagner <tw@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */
class Response implements HttpServletResponse
{

    /**
     *
     * @var string
     */
    protected $bodyStream = '';

    /**
     *
     * @var array
     */
    protected $headers = array();

    /**
     *
     * @var array
     */
    protected $cookies = array();

    /**
     *
     * @var array
     */
    protected $acceptedEncodings = array();

    /**
     * Defines the response status code
     *
     * @var int
     */
    protected $statusCode;

    /**
     * Defines the response reason phrase
     *
     * @var string
     */
    protected $statusReasonPhrase;

    /**
     * Defines the response mime type
     *
     * @var string
     */
    protected $mimeType = "text/plain";

    /**
     * Set's accepted encodings data
     *
     * @param array $acceptedEncodings The accepted codings as array
     *
     * @return void
     */
    public function setAcceptedEncodings($acceptedEncodings)
    {
        $this->acceptedEncodings = $acceptedEncodings;
    }

    /**
     * Return's accepted encodings data
     *
     * @return array
     */
    public function getAcceptedEncodings()
    {
        return $this->acceptedEncodings;
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
     * Appends the content.
     *
     * @param string $content The content to append
     *
     * @return void
     */
    public function appendBodyStream($content)
    {
        $this->bodyStream .= $content;
    }

    /**
     * Adds a cookie
     *
     * @param \TechDivision\Servlet\Http\Cookie $cookie The cookie instance to add
     *
     * @return void
     */
    public function addCookie(Cookie $cookie)
    {
        $this->cookies[] = $cookie;
    }

    /**
     * Returns TRUE if the response already has a cookie with the passed
     * name, else FALSE.
     *
     * @param string $cookieName Name of the cookie to be checked
     *
     * @return boolean TRUE if the response already has the cookie, else FALSE
     */
    public function hasCookie($cookieName)
    {
        foreach ($this->cookies as $cookie) {
            if ($cookie->getName() === $cookieName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns the cookies array.
     *
     * @return array
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Sets the headers.
     *
     * @param array $headers The headers array
     *
     * @return void
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
    }

    /**
     * Returns the headers array.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Adds a header to array
     *
     * @param string     $header The header label e.g. Accept or Content-Length
     * @param string|int $value  The header value
     *
     * @return void
     */
    public function addHeader($header, $value)
    {
        $this->headers[$header] = $value;
    }

    /**
     * Returns header info by given key
     *
     * @param string $key The headers key to return
     *
     * @return string|null
     */
    public function getHeader($key)
    {
        if (array_key_exists($key, $this->headers)) {
            return $this->headers[$key];
        }
    }

    /**
     * Returns http response code number only
     *
     * @return string
     */
    public function getCode()
    {
        list ($version, $code) = explode(" ", $this->getHeader(HttpProtocol::HEADER_STATUS));
        return $code;
    }

    /**
     * Returns response http version
     *
     * @return string
     */
    public function getVersion()
    {
        list ($version, $code) = explode(" ", $this->getHeader(HttpProtocol::HEADER_STATUS));
        return $version;
    }

    /**
     * Removes one single header from the headers array.
     *
     * @param string $header The header to remove
     *
     * @return void
     */
    public function removeHeader($header)
    {
        if (array_key_exists($header)) {
            unset($this->headers[$header]);
        }
    }

    /**
     * Returns the mime type of response data
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Sets the specific mime type
     *
     * @param string $mimeType The mime type to set
     *
     * @return void
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
    }

    /**
     * Set's the http response status code
     *
     * @param int $code The status code to set
     *
     * @return void
     */
    public function setStatusCode($code)
    {
        // set status code
        $this->statusCode = $code;
        // lookup reason phrase by code and set
        $this->setStatusReasonPhrase(HttpProtocol::getStatusReasonPhraseByCode($code));
    }

    /**
     * Return's the response status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Sets the status reason phrase
     *
     * @param string $statusReasonPhrase The reason phrase
     *
     * @return void
     */
    public function setStatusReasonPhrase($statusReasonPhrase)
    {
        $this->statusReasonPhrase = $statusReasonPhrase;
    }

    /**
     * Returns the status phrase based on the status code
     *
     * @return string
     */
    public function getStatusReasonPhrase()
    {
        return $this->statusReasonPhrase;
    }

    /**
     * Sets the http response status line
     *
     * @param string $statusLine The http response status line
     *
     * @return void
     */
    public function setStatusLine($statusLine)
    {
        $this->statusLine = $statusLine;
    }

    /**
     * Returns http response status line
     *
     * @link http://www.w3.org/Protocols/rfc2616/rfc2616-sec6.html
     * @return string
     */
    public function getStatusLine()
    {
        return $this->statusLine;
    }
}
