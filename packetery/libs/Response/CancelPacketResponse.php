<?php
/**
 * @author    Packeta s.r.o. <e-commerce.support@packeta.com>
 * @copyright 2017 Packeta s.r.o.
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */
declare(strict_types=1);

namespace Packetery\Response;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CancelPacketResponse extends BaseResponse
{
    /**
     * Checks if cancel is possible.
     *
     * @return bool
     */
    public function hasCancelNotAllowedFault(): bool
    {
        return $this->fault === 'CancelNotAllowedFault';
    }
}
