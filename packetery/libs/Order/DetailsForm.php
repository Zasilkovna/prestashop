<?php

namespace Packetery\Order;

use Packetery\Exceptions\DatabaseException;
use ReflectionException;
use Tools;
use Packetery;

class DetailsForm
{
	/** @var Packetery */
	private $module;

	public function __construct(Packetery $module) {
		$this->module = $module;
	}

	/**
	 * @param $messages
	 * @param $packeteryOrder
	 * @return void
	 * @throws DatabaseException
	 * @throws ReflectionException
	 */
	public function processOrderDetailChange(&$messages, $packeteryOrder)
	{
		if ($packeteryOrder['exported']) {
			return;
		}

		$packageDimensions = [];
		$size = ['length', 'height', 'width'];
		foreach ($size as $dimension) {
			$packageDimensions[$dimension] = Tools::getValue($dimension) !== '' ? (int) Tools::getValue($dimension) : '';
		}

		foreach ($packageDimensions as $dimensionType => $value) {
			if ($value < 1 && $value !== '') {
				$messages[] = [
					'text' => $this->module->l(ucfirst($dimensionType) . ' must be a number, greater than 0.'),
					'class' => 'danger',
				];
			}
		}

		foreach ($messages as $message) {
			if ($message['class'] === 'danger') {
				return;
			}
		}

		$updateOrderDetails = $this->saveOrderDetailsChange($packageDimensions);
		if ($updateOrderDetails) {
			$messages[] = [
				'text' => $this->module->l('Order details have been updated'),
				'class' => 'success'
			];
		} else {
			$messages[] = [
				'text' => $this->module->l('Order details could not be updated.'),
				'class' => 'danger',
			];
		}
	}

	/**
	 * @param array $orderDetails
	 * @return bool
	 * @throws DatabaseException
	 * @throws ReflectionException
	 */
	private function saveOrderDetailsChange($orderDetails)
	{
		$orderId = (int)Tools::getValue('order_id');
		$packeteryOrderFields = [
			'length' => $orderDetails['length'],
			'height' => $orderDetails['height'],
			'width' => $orderDetails['width'],
		];
		/** @var OrderRepository $orderRepository */
		$orderRepository = $this->module->diContainer->get(OrderRepository::class);
		return $orderRepository->updateByOrder($packeteryOrderFields, $orderId, true);
	}
}