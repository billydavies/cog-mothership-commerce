<?php

namespace Message\Mothership\Commerce\Order\Entity\Item;

use Message\User\UserInterface;

use Message\Mothership\Commerce\Order;

use Message\Cog\DB;
use Message\Cog\ValueObject\DateTimeImmutable;

/**
 * Order item creator.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class Create implements DB\TransactionalInterface
{
	protected $_currentUser;
	protected $_query;

	public function __construct(DB\Transaction $query, UserInterface $currentUser)
	{
		$this->_query       = $query;
		$this->_currentUser = $currentUser;
	}

	public function setTransaction(DB\Transaction $trans)
	{
		$this->_query = $trans;
	}

	public function create(Item $item)
	{
		// Set create authorship data if not already set
		if (!$item->authorship->createdAt()) {
			$item->authorship->create(
				new DateTimeImmutable,
				$this->_currentUser->id
			);
		}

		$this->_validate($item);

		$this->_query->add('
			INSERT INTO
				order_item
			SET
				order_id          = :orderID?i,
				created_at        = :createdAt?d,
				created_by        = :createdBy?in,
				list_price        = :listPrice?f,
				net               = :net?f,
				discount          = :discount?f,
				tax               = :tax?f,
				tax_rate          = :taxRate?f,
				gross             = :gross?f,
				rrp               = :rrp?fn,
				product_id        = :productID?in,
				product_name      = :productName?sn,
				unit_id           = :unitID?in,
				unit_revision     = :unitRevision?in,
				sku               = :sku?sn,
				barcode           = :barcode?sn,
				options           = :options?sn,
				brand             = :brand?sn,
				weight_grams      = :weight?in,
				stock_location 	  = :stockLocation?i
		', array(
			'orderID'       => $item->order->id,
			'createdAt'     => $item->authorship->createdAt(),
			'createdBy'     => $item->authorship->createdBy(),
			'listPrice'     => $item->listPrice,
			'net'           => $item->net,
			'discount'      => $item->discount,
			'tax'           => $item->tax,
			'taxRate'       => $item->taxRate,
			'gross'         => $item->gross,
			'rrp'           => $item->rrp,
			'productID'     => $item->productID,
			'productName'   => $item->productName,
			'unitID'        => $item->unitID,
			'unitRevision'  => $item->unitRevision,
			'sku'           => $item->sku,
			'barcode'       => $item->barcode,
			'options'       => $item->options,
			'brand'         => $item->brand,
			'weight'        => $item->weight,
			'stockLocation' => $item->stockLocation->id,
		));

		$this->_query->setIDVariable('ITEM_ID');
		$item->id = '@ITEM_ID';

		// Set the initial status, if defined
		if ($item->status) {
			if (!$item->status->authorship->createdAt()) {
				$item->status->authorship->create(
					$item->authorship->createdAt(),
					$item->authorship->createdBy()
				);
			}

			$this->_query->add('
				INSERT INTO
					order_item_status
				SET
					order_id    = :orderID?i,
					item_id     = :itemID?i,
					status_code = :code?i,
					created_at  = :createdAt?d,
					created_by  = :createdBy?in
			', array(
				'orderID'   => $item->order->id,
				'itemID'    => $item->id,
				'code'      => $item->status->code,
				'createdAt' => $item->status->authorship->createdAt(),
				'createdBy' => $item->status->authorship->createdBy(),
			));
		}

		// Set personalisation data, if defined
		if ($item->personalisation && !$item->personalisation->isEmpty()) {
			$this->_query->add('
				INSERT INTO
					order_item_personalisation
				SET
					item_id         = :itemID?i,
					sender_name     = :senderName?sn,
					recipient_name  = :recipientName?sn,
					recipient_email = :recipientEmail?sn,
					message         = :message?sn
			', array(
				'itemID'         => $item->id,
				'senderName'     => $item->personalisation->senderName,
				'recipientName'  => $item->personalisation->recipientName,
				'recipientEmail' => $item->personalisation->recipientEmail,
				'message'        => $item->personalisation->message,
			));
		}

		// TODO: use item loader to re-load this item and return it ONLY IF NOT IN ORDER CREATION TRANSACTION
		return $item;
	}

	protected function _validate(Item $item)
	{
		if ($item->personalisation && !($item->personalisation instanceof Personalisation)) {
			throw new \InvalidArgumentException('Item personalisation must be an instance of `Personalisation`');
		}
	}
}