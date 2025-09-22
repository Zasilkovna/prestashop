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
namespace Packetery\Order;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Packetery\Exceptions\DatabaseException;

class Tracking
{
    /** @var OrderRepository */
    private $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Returns packetery order tracking number
     *
     * @param string $id_orders Comma separated integers
     *
     * @return array
     *
     * @throws DatabaseException
     */
    public function getTrackingFromOrders($id_orders)
    {
        $result = $this->orderRepository->getTrackingNumbers($id_orders);
        $tracking = [];
        if ($result) {
            foreach ($result as $tn) {
                $tracking[$tn['id_order']] = $tn['tracking_number'];
            }
        }

        return $tracking;
    }

    /**
     * Updates eshop and packetery order tracking number
     *
     * @param int $id_order
     * @param string $tracking_number numeric
     *
     * @return bool
     *
     * @throws DatabaseException
     */
    public function updateOrderTrackingNumber($id_order, $tracking_number)
    {
        if (!isset($id_order, $tracking_number)) {
            return false;
        }
        if ($this->orderRepository->existsByOrder((int) $id_order)) {
            return $this->orderRepository->setTrackingNumber((int) $id_order, $tracking_number);
        }

        return false;
    }
}
