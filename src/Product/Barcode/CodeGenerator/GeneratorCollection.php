<?php

namespace Message\Mothership\Commerce\Product\Barcode\CodeGenerator;

use Message\Cog\ValueObject\Collection;
use Message\Mothership\Commerce\Product\Barcode;

class GeneratorCollection extends Collection
{
	protected function _configure()
	{
		$this->setType('\\Message\\Mothership\\Commerce\\Product\\Barcode\\CodeGenerator\\GeneratorInterface');

		$this->addValidator(function ($item) {
			if (!Barcode\ValidTypes::isValid($item->getBarcodeType())) {
				if (!is_string($item->getBarcodeType())) {
					throw new \InvalidArgumentException(
						'`GeneratorInterface::getBarcodeType()` must return a string, , returns ' . gettype($item->getBarcodeType())
					);
				}
				throw new \LogicException('`' . $item->getBarcodeType() . '` is not a valid barcode type');
			}
		});

		$this->setKey(function ($item) {
			return $item->getName();
		});
	}

	public function getByType($type, $asArray = false)
	{
		if (!is_string($type)) {
			throw new \InvalidArgumentException('Type must be a string, ' . gettype($type) . ' given');
		}

		if (!Barcode\ValidTypes::isValid($type)) {
			throw new \LogicException('`' . $type . '` is not a valid barcode type');
		}

		$matches = [];

		foreach ($this as $generator) {
			if ($generator->getBarcodeType() === $type) {
				if (!$asArray) {
					return $generator;
				}

				$matches[$generator->getName()] = $generator;
			}
		}

		return $matches;
	}
}