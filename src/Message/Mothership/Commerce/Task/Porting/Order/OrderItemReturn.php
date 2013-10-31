<?php

namespace Message\Mothership\Commerce\Task\Porting\Order;

use Message\Mothership\Commerce\Task\Porting\Porting;

class OrderItemReturn extends Porting
{
    public function process()
    {
        $uwOld = $this->getFromConnection();
		$uwNew = $this->getToCOnnection();

		$new = new \Message\Cog\DB\Transaction($uwNew);
		$old = new \Message\Cog\DB\Query($uwOld);

		$sql = 'SELECT
					return_id,
					order_item_return.order_id,
					order_item_return.item_id,
					UNIX_TIMESTAMP(return_datetime) AS created_at,
					user_id AS created_by,
					UNIX_TIMESTAMP(package_received_date) AS updated_at,
					NULL AS updated_by,
					IF (return_status_id = 55, UNIX_TIMESTAMP(package_received_date), NULL) AS completed_at,
					NULL AS completed_by,
					return_exchange_item_id AS exchange_item_id,
					IF (package_received_date IS NULL, 2000, 2100) AS status_id,
					CASE return_reason_id
						WHEN 1 THEN \'doesnt-suit-me\'
						WHEN 2 THEN \'not-as-expected\'
						WHEN 3 THEN \'wrong-colour\'
						WHEN 4 THEN \'wrong-colour\'
						WHEN 5 THEN \'doesnt-fit-me\'
						WHEN 6 THEN \'doesnt-fit-me\'
						WHEN 7 THEN \'doesnt-suit-me\'
						WHEN 8 THEN \'wrong-item-sent\'
						WHEN 9 THEN \'not-as-expected\'
						WHEN 10 THEN \'ordered-two-sizes-for-fit-returning-one\'
						WHEN 12 THEN \'doesnt-fit-me\'
						WHEN 13 THEN \'not-as-expected\'
					END AS reason,
					IF (return_resolution_name = "sizestyle_exchange", "exchange", return_resolution_name) AS resolution,
					IFNULL(balancing_payment,0) AS balance,
					IFNULL(balancing_payment,0) AS calculated_balance,
					item_price - IFNULL(item_discount,0) AS returned_value,
					return_destination_id AS return_to_stock_location_id,
					order_item_return.accepted as accepted,
				FROM
					order_item_return
				JOIN order_item ON (order_item.item_id = order_item_return.item_id)
				JOIN order_summary ON (order_item_return.order_id = order_summary.order_id)
				JOIN order_return_reason USING (return_reason_id)
				JOIN order_return_resolution USING (return_resolution_id)';

		$result = $old->run($sql);
		$output= '';
		$new->add('TRUNCATE order_item_return');

		foreach($result as $row) {
			$new->add('
				INSERT INTO
					order_item_return
				(
					return_id,
					order_id,
					item_id,
					created_at,
					created_by,
					updated_at,
					updated_by,
					completed_at,
					completed_by,
					exchange_item_id,
					status_id,
					reason,
					resolution,
					balance,
					calculated_balance,
					accepted,
					returned_value,
					return_to_stock_location_id
				)
				VALUES
				(
					:return_id?,
					:order_id?,
					:item_id?,
					:created_at?,
					:created_by?,
					:updated_at?,
					:updated_by?,
					:completed_at?,
					:completed_by?,
					:exchange_item_id?,
					:status_id?,
					:reason?,
					:resolution?,
					:balance?,
					:calculated_balance?,
					:accepted?,
					:returned_value?,
					:return_to_stock_location_id?
				)', (array) $row
			);
		}

		if ($new->commit()) {
        	$this->writeln('<info>Successfully ported order item return</info>');
		}

		return true;
    }
}