<?php

/**
 * TechDivision\ServletEngine\Authentication\DigestAuthentication
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

use TechDivision\Http\HttpProtocol;

/**
 * A digest authentication implementation.
 *
 * @category   Appserver
 * @package    TechDivision_ServletEngine
 * @subpackage Authentication
 * @author     Philipp Dittert <pd@techdivision.com>
 * @copyright  2014 TechDivision GmbH <info@techdivision.com>
 * @license    http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link       http://www.appserver.io
 */
class DigestAuthentication extends AbstractAuthentication
{
    
    /**
     * Authenticate the request against digest backend.
     *
     * @return boolean TRUE if authentication has been successfull, else FALSE
     */
    public function authenticate()
    {
        $config = $this->getServlet()->getSecuredUrlConfig();
        $req = $this->getServletRequest();
        $res = $this->getServletResponse();

        $realm = $config['realm'];
        $adapterType = $config['adapter_type'];
        $options = $config['options'];

        // if client provided authentication data
        if ($authorizationData = $req->getHeader(HttpProtocol::HEADER_AUTHORIZATION)) {
            
            // check if Authentication is DIGEST
            if (substr($authorizationData, 0, 6) == AbstractAuthentication::AUTHENTICATION_METHOD_DIGEST) {

                $data = array();
                $parts = explode(", ", substr($authorizationData, 7));

                foreach ($parts as $element) {
                    $bits = explode("=", $element);
                    $data[$bits[0]] = str_replace('"', '', $bits[1]);
                }

                // instantiate configured authentication adapter
                $authAdapter = $this->getServlet()->getServletContext()->getApplication()->newInstance(
                    'TechDivision\ServletEngine\Authentication\Adapters\\' . ucfirst($adapterType) . 'Adapter',
                    array($options, $this->getServlet())
                );

                // delegate authentication to adapter
                if ($authAdapter->authenticate($data, $req->getMethod())) {
                    return true;
                }
            }
        }

        // either authentication data was not provided or authentication failed
        $res->setStatusCode(401);
        $res->addHeader(HttpProtocol::HEADER_WWW_AUTHENTICATE, AbstractAuthentication::AUTHENTICATION_METHOD_DIGEST . ' ' . 'realm="' . $realm . '",qop="auth",nonce="' . uniqid() . '",opaque="' . md5($realm) .'"');
        $res->appendBodyStream("<html><head><title>401 Authorization Required</title></head><body><h1>401 Authorization Required</h1><p>This server could not verify that you are authorized to access the document requested. Either you supplied the wrong credentials (e.g., bad password), or your browser doesn't understand how to supply the credentials required. Confused</p></body></html>");
        return false;
    }
}
