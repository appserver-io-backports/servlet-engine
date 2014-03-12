<?php

/**
 * TechDivision\ServletEngine\WebContainerDeployment
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
use TechDivision\ApplicationServer\Api\Node\NodeInterface;
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
class WebContainerDeployment implements Deployment
{

    /**
     * The container node the deployment is for.
     *
     * @var \TechDivision\ApplicationServer\Api\Node\NodeInterface
     */
    protected $containerNode;
    
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
     * Initializes the deployment with the container thread.
     *
     * @param \TechDivision\ApplicationServer\Interfaces\ContextInterface $initialContext The initial context instance
     * @param \TechDivision\ApplicationServer\Api\Node\NodeInterface      $containerNode  The container node the deployment is for
     *
     * @return void
     */
    public function __construct(ContextInterface $initialContext, NodeInterface $containerNode)
    {
        $this->initialContext = $initialContext;
        $this->containerNode = $containerNode;
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
     * Injects the document root containing the web applications.
     * 
     * @param string $documentRoot The document root
     * 
     * @return void
     */
    public function injectDocumentRoot($documentRoot)
    {
        $this->documentRoot = $documentRoot;
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
     * Returns the document root containing the web applications.
     * 
     * @return string The document root
     */
    public function getDocumentRoot()
    {
        return $this->documentRoot;
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
     * Returns the container node the deployment is for.
     *
     * @return \TechDivision\ApplicationServer\Api\Node\NodeInterface The container node
     */
    public function getContainerNode()
    {
        return $this->containerNode;
    }
    
    /**
     * Connects the passed application to the system configuration.
     *
     * @param \TechDivision\ServletEngine\Application $application The application to be prepared
     *
     * @return void
     */
    protected function addApplicationToSystemConfiguration(Application $application)
    {
    
        // create a new API app service instance
        $appService = $this->newService('TechDivision\ApplicationServer\Api\AppService');
        $appNode = $appService->loadByWebappPath($application->getWebappPath());
    
        // check if the application has already been attached to the container
        if ($appNode == null) {
            $application->newAppNode($this->getContainerNode());
        } else {
            $application->setAppNode($appNode);
        }
    
        // persist the application
        $appService->persist($application->getAppNode());
    
        // connect the application to the container
        $application->connect();
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
     * (non-PHPdoc)
     *
     * @param string $className The fully qualified class name to return the instance for
     * @param array  $args      Arguments to pass to the constructor of the instance
     *
     * @return object The instance itself
     * @see \TechDivision\ApplicationServer\InitialContext::newInstance()
     */
    public function newInstance($className, array $args = array())
    {
        return $this->getInitialContext()->newInstance($className, $args);
    }
    
    /**
     * (non-PHPdoc)
     *
     * @param string $className The API service class name to return the instance for
     *
     * @return \TechDivision\ApplicationServer\Api\ServiceInterface The service instance
     * @see \TechDivision\ApplicationServer\InitialContext::newService()
     */
    public function newService($className)
    {
        return $this->getInitialContext()->newService($className);
    }
    
    /**
     * (non-PHPdoc)
     *
     * @param string|null $directoryToAppend Append this directory to the base directory before returning it
     *
     * @return string The base directory
     * @see \TechDivision\ApplicationServer\Api\ContainerService::getBaseDirectory()
     */
    public function getBaseDirectory($directoryToAppend = null)
    {
        return $this->newService('TechDivision\ApplicationServer\Api\ContainerService')->getBaseDirectory($directoryToAppend);
    }

    /**
     * (non-PHPdoc)
     *
     * @return string The application base directory for this container
     * @see \TechDivision\ApplicationServer\Api\ContainerService::getAppBase()
     */
    public function getAppBase()
    {
        return $this->getDocumentRoot();
    }

    /**
     * Returns an array with available applications.
     *
     * @return \TechDivision\ServletEngine\Deployment The deployment instance
     */
    public function deploy()
    {

        // gather all the deployed web applications
        foreach (new \FilesystemIterator($this->getAppBase()) as $folder) {
            
            // check if file or subdirectory has been found
            if ($folder->isDir() === true) {

                // initialize the application instance
                $application = $this->newInstance(
                    '\TechDivision\ServletEngine\WebContainerApplication',
                    array(
                        $this->getInitialContext(),
                        $this->getContainerNode(),
                        $folder->getBasename()
                    )
                );

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

        // adds the application to the system configuration
        $this->addApplicationToSystemConfiguration($application);
        
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

        // log a message that the app has been started
        $this->getInitialContext()->getSystemLogger()->debug(
            sprintf(
                'Successfully started app %s in container %s',
                $application->getName(),
                $application->getWebappPath(),
                $application->getContainerNode()->getName()
            )
        );
    }
}
