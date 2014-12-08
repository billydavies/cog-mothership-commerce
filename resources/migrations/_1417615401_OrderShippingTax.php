<?php

use Message\Cog\Migration\Adapter\MySQL\Migration;

class _1417615401_OrderShippingTax extends Migration
{
	public function up()
	{
		$this->run("
			CREATE TABLE IF NOT EXISTS `order_shipping_tax` (
			  `order_id` int(11) unsigned NOT NULL,
			  `tax_type` varchar(30) NOT NULL,
			  `tax_rate` decimal(10,3) unsigned NOT NULL,
			  `tax_amount` decimal(10,2) unsigned NOT NULL,
			  PRIMARY KEY (`order_id`,`tax_type`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;
		");
	}

	public function down()
	{
		$this->run("DROP TABLE IF EXISTS `order_shipping_tax`; ");
	}
}