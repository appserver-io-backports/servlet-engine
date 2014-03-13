<?php
/**
 * TechDivision\ServletEngine\VirtualHost
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */

namespace TechDivision\ServletEngine;

/**
 * A basic virtual host class containing virtual host meta information like 
 * domain name and base directory.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class VirtualHost
{

    /**
     * The vhost domain name.
     *
     * @var string
     */
    protected $name;

    /**
     * The vhost base directory relative to webapps directory.
     *
     * @var string
     */
    protected $appBase;

    /**
     * Initializes the vhost with the necessary information.
     *
     * @param string $name    The vhost's domain name
     * @param string $appBase The vhost's base directory
     * 
     * @return void
     */
    public function __construct($name, $appBase)
    {
        $this->name = $name;
        $this->appBase = $appBase;
    }

    /**
     * Returns the vhost's domain name.
     *
     * @return string The vhost's domain name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the vhost's base directory.
     *
     * @return string The vhost's base directory
     */
    public function getAppBase()
    {
        return $this->appBase;
    }
    
    /**
     * Returns TRUE if the application matches this virtual host configuration.
     * 
     * @param \TechDivision\ServletEngine\Application $application The application to match
     * 
     * @return boolean TRUE if the application matches this virtual host, else FALSE
     */
    public function match(Application $application)
    {
        return trim($this->getAppBase(), '/') === $application->getName();
    }
}
