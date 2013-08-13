<?php

namespace Message\Mothership\Commerce\Product\Stock\Movement;

use Message\Mothership\Commerce\Product\Unit\Unit;
use Message\Mothership\Commerce\Product\Product;
use Message\Mothership\Commerce\Product\Stock\Location\Location;

class PartialMovement extends Movement
{
	protected $_product;
	protected $_unit;
	protected $_location;

	public function getProduct()
	{
		return $this->_product;
	}

	public function setProduct(Product $product)
	{
		if($_unit) {
			throw new \LogicException('Cannot set product-requirement when unit-requirement is already set.');
		}
		$this->_product = product;

		return $this;
	}

	public function getUnit()
	{
		return $this->_unit;
	}

	public function setUnit(Unit $unit)
	{
		if($_product) {
			throw new \LogicException('Cannot set unit-requirement when product-requirement is already set.');
		}
		$this->_unit = $unit;

		return $this;
	}

	public function getLocation()
	{
		return $this->_location;
	}

	public function setLocation(Location $location)
	{
		$this->_location = $location;

		return $this;
	}

	protected function _loadAdjustments()
	{
		$this->_adjustments = $this->_adjustmentLoader->getByPartialMovement($this);
	}
}