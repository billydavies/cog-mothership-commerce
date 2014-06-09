<?php

namespace Message\Mothership\Commerce\Order\Entity;

use Message\Mothership\Commerce\Order\Order;
use Message\Mothership\Commerce\Order\Loader as OrderLoader;

/**
 * Base entity loader.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
abstract class BaseLoader implements LoaderInterface
{
	protected $_orderLoader;

	/**
	 * {@inheritDoc}
	 */
	public function setOrderLoader(OrderLoader $orderLoader)
	{
		$this->_orderLoader = $orderLoader;

		$this->_orderLoader->includeDeleted(true);
	}
}