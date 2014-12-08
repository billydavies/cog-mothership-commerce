<?php

namespace Message\Mothership\Commerce\Product\Upload;

use Message\Mothership\Commerce\Product\Unit\Unit;
use Message\Mothership\Commerce\Product\Product;
use Message\User\UserInterface;
use Message\Cog\Localisation\Locale;
use Message\Cog\ValueObject\Authorship;

class UnitBuilder
{
	/**
	 * @var HeadingKeys
	 */
	private $_headingKeys;

	/**
	 * @var Validate
	 */
	private $_validator;

	/**
	 * @var \Message\Mothership\Commerce\Product\Unit\Unit
	 */
	private $_unit;

	private $_priceTypes = [
		'retail',
		'rrp',
		'cost',
	];

	public function __construct(
		HeadingKeys $headingKeys,
		Validate $validator,
		Locale $locale,
		UserInterface $user,
		array $currencies,
		$defaultCurrency
	)
	{
		$this->_headingKeys = $headingKeys;
		$this->_validator   = $validator;
		$this->_locale      = $locale;
		$this->_currencies  = $currencies;
		$this->_unit        = new Unit($this->_locale, $this->_priceTypes, $defaultCurrency);
		$this->_user        = $user;
	}

	public function setBaseProduct(Product $product)
	{
		$this->_unit->product = $product;

		return $this;
	}

	public function build(array $row)
	{
		if (null === $this->_unit->product) {
			throw new \LogicException('Base product not set for unit creation!');
		}

		if (!$this->_validator->validateRow($row)) {
			throw new Exception\UploadException('Row is not valid!');
		}

		$this->_setOptions($row);
		$this->_setPrices($row);
		$this->_setData($row);
		$this->_addAuthorship();

		$this->_unit->stock = [];

		return $this->_unit;
	}

	private function _addAuthorship()
	{
		$authorship = new Authorship;
		$authorship->create(null, $this->_user);
		$this->_unit->authorship = $authorship;
	}

	private function _setOptions(array $row)
	{
		for ($i = 1; $i <= HeadingKeys::NUM_VARIANTS; ++$i) {
			$nameKey = $this->_headingKeys->getKey(HeadingKeys::VAR_NAME_PREFIX . $i);

			if ($row[$nameKey]) {
				$valueKey = $this->_headingKeys->getKey(HeadingKeys::VAR_VAL_PREFIX . $i);
				$this->_unit->setOption($row[$nameKey], $row[$valueKey]);
			}
		}

		return $this;
	}

	private function _setPrices(array $row)
	{
		foreach ($this->_priceTypes as $type) {
			foreach ($this->_currencies as $currency) {
				$key = $this->_headingKeys->getKey($type . '.' . $currency);
				$price = $row[$key];
				if ($price && $price !== $this->_getProductPrice($type, $currency)) {
					$this->_unit->setPrice($price, $type, $currency);
				}
			}
		}

		return $this;
	}

	private function _setData(array $row)
	{
		foreach ($row as $key => $value) {
			$key = $this->_headingKeys->getKey($key);
			if ($value && property_exists($this->_unit, $key)) {
				$this->_unit->$key = $key;
			}
		}

		return $this;
	}

	private function _getProductPrice($type, $currency)
	{
		return (float) $this->_unit->getPrice($type, $currency);
	}

}