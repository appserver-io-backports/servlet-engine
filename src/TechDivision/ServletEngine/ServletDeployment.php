<?php

/**
 * TechDivision\ServletEngine\ServletDeployment
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

use TechDivision\ServletEngine\Application;
use TechDivision\ApplicationServer\Interfaces\ContextInterface;

/**
 * Class Deployment
 *
 * @category  Appserver
 * @package   TechDivision_ServletContainer
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class ServletDeployment implements Deployment
{
    
    /**
     * Array with the initialized applications.
     *
     * @var array
     */
    protected $applications = array();
    
    /**
     * The initial context instance.
     *
     * @var \TechDivision\ApplicationServer\Interfaces\ContextInterface
     */
    protected $initialContext;
    
    /**
     * Array containing the virtual hosts.
     * 
     * @var array
     */
    protected $virtualHosts;
    
    /**
     * The document root containing the web applications.
     * 
     * @var string
     */
    protected $documentRoot;
    
    /**
     * The servlet engines base directory.
     * 
     * @var string
     */
    protected $baseDirectory;
    
    /**
     * The path, relative to the base directory, containing the web applications.
     * 
     * @var string
     */
    protected $appBase;
    
    /**
     * Injects the initial context instance.
     * 
     * @param \TechDivision\ApplicationServer\Interfaces\ContextInterface $initialContext The initial context instance
     * 
     * @return void
     */
    public function injectInitialContext(ContextInterface $initialContext)
    {
        $this->initialContext = $initialContext;
    }
    
    /**
     * Injects the servlet engines base directory.
     * 
     * @param string $baseDirectory The servlet engines base directory
     * 
     * @return void
     */
    public function injectBaseDirectory($baseDirectory)
    {
        $this->baseDirectory = $baseDirectory;
    }
    
    /**
     * Injects path, relative to the base directory, containing the web applications.
     * 
     * @param string $appBase The path, relative to the base directory, containing the web applications
     * 
     * @return void
     */
    public function injectAppBase($appBase)
    {
        $this->appBase = $appBase;
    }
    
    /**
     * Injects the array with the virtual host configuration.
     * 
     * @param array $virtualHosts Array with the virtual host configuration
     * 
     * @return void
     */
    public function injectVirtualHosts(array $virtualHosts)
    {
        $this->virtualHosts = $virtualHosts;
    }
    
    /**
     * Returns the array with the virtual host configuration.
     * 
     * @return array The array with the virtual host configuration
     */
    public function getVirtualHosts()
    {
        return $this->virtualHosts;
    }
    
    /**
     * Returns the initialContext instance
     *
     * @return \TechDivision\ApplicationServer\Interfaces\ContextInterface The initial context instance
     */
    public function getInitialContext()
    {
        return $this->initialContext;
    }
    
    /**
     * Return's the deployed applications.
     *
     * @return array The deployed applications
     */
    public function getApplications()
    {
        return $this->applications;
    }

    /**
     * Returns the servlet engines base directory.
     *
     * @param string $directoryToAppend The directory to append to the base directory
     *
     * @return string The base directory with appended dir if given
     */
    public function getBaseDirectory($directoryToAppend = null)
    {
        $baseDirectory = $this->baseDirectory;
        if ($directoryToAppend != null) {
            $baseDirectory .= $directoryToAppend;
        }
        return $baseDirectory;
    }

    /**
     * Returns the path, relative to the base directory, containing the web applications.
     *
     * @return string The redirectory containing the web applications
     */
    public function getAppBase()
    {
        return $this->appBase;
    }

    /**
     * Returns an array with available applications.
     *
     * @return \TechDivision\ServletEngine\Deployment The deployment instance
     */
    public function deploy()
    {
        
        // gather all the deployed web applications
        foreach (new \FilesystemIterator($this->getBaseDirectory($this->getAppBase())) as $folder) {
            
            // check if file or subdirectory has been found
            if ($folder->isDir() === true) {
                
                // initialize the application instance
                $application = new ServletApplication();
                $application->injectName($folder->getBasename());
                $application->injectInitialContext($this->getInitialContext());
                $application->injectBaseDirectory($this->getBaseDirectory());
                $application->injectAppBase($this->getAppBase());

                // add the application to the available applications
                $this->addApplication($application);
            }
        }

        // return initialized applications
        return $this;
    }

    /**
     * Append the deployed application to the deployment instance
     * and registers it in the system configuration.
     *
     * @param \TechDivision\ServletEngine\Application $application The application to append
     *
     * @return void
     */
    public function addApplication(Application $application)
    {

        // initialize and connect the application
        $application->connect();
        
        /*
         * Build an array with patterns as key and an array with application name and document root as value. This
         * helps to improve speed when matching an request to find the application to handle it.
         *
         * The array looks something like this:
         *
         * /^www.appserver.io(\/([a-z0-9+\$_-]\.?)+)*\/?/               => application
         * /^appserver.io(\/([a-z0-9+\$_-]\.?)+)*\/?/                   => application
         * /^appserver.local(\/([a-z0-9+\$_-]\.?)+)*\/?/                => application
         * /^neos.local(\/([a-z0-9+\$_-]\.?)+)*\/?/                     => application
         * /^neos.appserver.io(\/([a-z0-9+\$_-]\.?)+)*\/?/              => application
         * /^[a-z0-9-.]*\/neos(\/([a-z0-9+\$_-]\.?)+)*\/?/              => application
         * /^[a-z0-9-.]*\/example(\/([a-z0-9+\$_-]\.?)+)*\/?/           => application
         * /^[a-z0-9-.]*\/magento-1.8.1.0(\/([a-z0-9+\$_-]\.?)+)*\/?/   => application
         *
         * This should also match request URI's like:
         *
         * 127.0.0.1:8586/magento-1.8.1.0/index.php/admin/dashboard/index/key/8394a99f7bd5f4aca531d7c752a5fdb1/
         */
        
        // iterate over a applications vhost/alias configuration
        foreach ($this->getVirtualHosts() as $vhost) {
            
            // check if the virtual host match the application
            if ($vhost->match($application)) {
                
                // bind the virtual host to the application
                $application->addVirtualHost($vhost);
                // add the application to the internal array
                $this->applications = array('/^' . $vhost->getName() . '(\/([a-z0-9+\$_-]\.?)+)*\/?/' => $application) + $this->applications;
            }
        }
        
        // finally APPEND a wildcard pattern for each application to the patterns array
        $this->applications = $this->applications + array('/^[a-z0-9-.]*\/' . $application->getName() . '(\/([a-z0-9+\$_-]\.?)+)*\/?/' => $application);
    }
}
