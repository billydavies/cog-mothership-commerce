<?php

namespace Message\Mothership\Commerce\Product\Upload;

use Message\Mothership\Commerce\Product;
use Message\Cog\Event\Event as Event;

class UnitCreateEvent extends Event
{
	private $_unit;
	private $_formData;
	private $_row;

	public function __construct(Product\Unit\Unit $unit, array $formData, array $row)
	{
		$this->setUnit($unit);
		$this->setFormData($formData);
		$this->setRow($row);
	}

	public function setUnit(Product\Unit\Unit $unit)
	{
		$this->_unit = $unit;
	}

	public function getUnit()
	{
		return $this->_unit;
	}

	public function getProduct()
	{
		return $this->_unit->product;
	}

	public function setFormData(array $data)
	{
		$this->_formData = $data;
	}

	public function getFormData()
	{
		return $this->_formData;
	}

	public function setRow(array $row)
	{
		$this->_row = $row;
	}

	public function getRow()
	{
		return $this->_row;
	}
}