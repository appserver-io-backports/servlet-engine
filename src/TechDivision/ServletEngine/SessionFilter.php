<?php

/**
 * TechDivision\ServletEngine\FilterIterator
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
 * A filter implementation to make sure, that only the newest, configurable session
 * files are preloaded when the session manager has been initialized.
 *
 * @category  Appserver
 * @package   TechDivision_ServletEngine
 * @author    Tim Wagner <tw@techdivision.com>
 * @copyright 2014 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      http://www.appserver.io
 */
class SessionFilter extends \FilterIterator
{

    protected $userFilter;

    public function __construct(\Iterator $iterator , $userFilter)
    {

        parent::__construct($iterator);

        $this->userFilter = $userFilter;
    }

    public function accept()
    {

        $splFileInfo = $this->getInnerIterator()->current();

        $aTime = time() - $this->userFilter;

        if ($splFileInfo->getATime() < $aTime) {
            return false;
        }

        return true;
    }

    public static function newInstance($sessionSavePath, $userFiler)
    {
        return new SessionFilter(new \GlobIterator($sessionSavePath), $userFilter);
    }
}
