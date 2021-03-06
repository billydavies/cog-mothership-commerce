<?php

namespace Message\Mothership\Commerce\Test\Product\Tax\Strategy;

use Mockery as m;
use Message\Mothership\Commerce\Product\Tax\Strategy\ExclusiveTaxStrategy;

class ExclusiveTaxStrategyTest extends \PHPUnit_Framework_TestCase
{
	protected $_taxRate;

	public function setUp()
	{
		$this->_taxRate = m::mock('Message\Mothership\Commerce\Product\Tax\Rate\TaxRate');
	}

	public function testGetNetPrice()
	{
		$strategy = new ExclusiveTaxStrategy;
		$price = 100;

		$this->assertEquals($price, $strategy->getNetPrice($price));
	}

	public function testGetGrossPrice()
	{
		$strategy = new ExclusiveTaxStrategy;
		$price = 100;

		$this->_taxRate->shouldReceive('getTaxRate')
			->zeroOrMoreTimes()
			->andReturn(20);

		$this->_taxRate->shouldReceive('getTaxedPrice')
			->with($price)
			->zeroOrMoreTimes()
			->andReturn(120);


		$this->assertEquals(120, $strategy->getGrossPrice($price, $this->_taxRate));
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testInvalidPriceException()
	{
		$strategy = new ExclusiveTaxStrategy;

		$strategy->getNetPrice('Not a string', $this->_taxRate);
	}
}