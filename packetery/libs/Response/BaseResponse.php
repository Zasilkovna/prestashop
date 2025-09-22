<?php
/**
 * 2017 Zlab Solutions
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Eugene Zubkov <magrabota@gmail.com>, RTsoft s.r.o
 *  @copyright Since 2017 Zlab Solutions
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
namespace Packetery\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

class BaseResponse
{
    /**
     * Fault identifier.
     *
     * @var ?string
     */
    protected $fault;

    /**
     * Fault string.
     *
     * @var ?string
     */
    private $faultString;

    /**
     * Checks if is faulty.
     *
     * @return bool
     */
    public function hasFault()
    {
        return (bool) $this->fault;
    }

    /**
     * Checks if password is faulty.
     *
     * @return bool
     */
    public function hasWrongPassword()
    {
        return 'IncorrectApiPasswordFault' === $this->fault;
    }

    /**
     * Sets fault identifier.
     *
     * @param string $fault fault identifier
     */
    public function setFault($fault)
    {
        $this->fault = $fault;
    }

    /**
     * Sets fault string.
     *
     * @param string $faultString fault string
     */
    public function setFaultString($faultString)
    {
        $this->faultString = $faultString;
    }

    /**
     * Gets fault string.
     *
     * @return string|null
     */
    public function getFaultString()
    {
        return $this->faultString;
    }
}
