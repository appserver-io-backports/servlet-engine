<?php

/**
 * TechDivision\ServletEngine\DefaultSessionSettingsTest
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
 * Test for the default session settings implementation.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class DefaultSessionSettingsTest extends \PHPUnit_Framework_TestCase
{

    /**
     * The settings instance to test.
     * 
     * @var \TechDivision\ServletEngine\DefaultSessionSettings
     */
    protected $settings;
    
    /**
     * Initializes the settings instance to test.
     *
     * @return void
     */
    public function setUp()
    {
        $this->settings = new DefaultSessionSettings();
    }
    
    /**
     * Test if the default session name is returned correctly.
     *
     * @return void
     */
    public function testGetSessionName()
    {
        $this->assertSame(DefaultSessionSettings::DEFAULT_SESSION_NAME, $this->settings->getSessionName());
    }
}
