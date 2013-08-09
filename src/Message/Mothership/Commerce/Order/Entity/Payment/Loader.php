<?php

namespace Message\Mothership\Commerce\Order\Entity\Payment;

use Message\Mothership\Commerce\Order;

use Message\Cog\DB;
use Message\Cog\ValueObject\DateTimeImmutable;

/**
 * Order payment loader.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class Loader extends Order\Entity\BaseLoader
{
	protected $_query;
	protected $_methods;

	public function __construct(DB\Query $query, MethodCollection $methods)
	{
		$this->_query   = $query;
		$this->_methods = $methods;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getByOrder(Order\Order $order)
	{
		$result = $this->_query->run('
			SELECT
				payment_id
			FROM
				order_payment
			WHERE
				order_id = ?i
		', $order->id);

		return $this->_load($result->flatten(), true, $order);
	}

	protected function _load($ids, $alwaysReturnArray = false, Order\Order $order = null)
	{
		if (!is_array($ids)) {
			$ids = (array) $ids;
		}

		if (!$ids) {
			return $alwaysReturnArray ? array() : false;
		}

		$result = $this->_query->run('
			SELECT
				*,
				payment_id AS id
			FROM
				order_payment
			WHERE
				payment_id IN (?ij)
		', array($ids));

		if (0 === count($result)) {
			return $alwaysReturnArray ? array() : false;
		}

		$entities = $result->bindTo('Message\\Mothership\\Commerce\\Order\\Entity\\Payment\\Payment');
		$return   = array();

		foreach ($result as $key => $row) {
			// Cast decimals to float
			$entities[$key]->amount = (float) $row->amount;

			$entities[$key]->authorship->create(
				new DateTimeImmutable(date('c', $row->created_at)),
				$row->created_by
			);

			if (!$order) {
				$order = $this->_orderLoader->getByID($row->order_id);
			}

			$entities[$key]->order = $order;

			// TODO: set the return here if a return_id is set

			$entities[$key]->method = $this->_methods->get($row->method);

			$return[$row->id] = $entities[$key];
		}

		return $alwaysReturnArray || count($return) > 1 ? $return : reset($return);
	}

}