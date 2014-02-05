<?php

namespace Message\Mothership\Commerce\Product;

use Message\Mothership\Commerce\Product\Product;
use Message\Cog\ValueObject\DateTimeImmutable;
use Message\Cog\Localisation\Locale;

use Message\User\UserInterface;

use Message\Cog\DB\Transaction;
use Message\Cog\DB\Result;

/**
 * Class for updating the attributes of a given Product object to the DB
 */
class Edit
{
	protected $_trans;
	protected $_user;
	protected $_locale;
	protected $_product;

	public function __construct(Transaction $trans, Locale $locale, UserInterface $user)
	{
		$this->_trans  = $trans;
		$this->_user   = $user;
		$this->_locale = $locale;
	}

	/**
	 * Handles the bulk updating of most of the product properties
	 *
	 * @param  Product $product Updated Product object to save
	 *
	 * @return Product          Saved Product object
	 */
	public function save(Product $product)
	{
		$this->_product = $product;

		$this->_saveProduct()
			->_saveProductInfo()
			->_saveProductExport();

		$this->_trans->commit();

		return $product;
	}

	/**
	 * Updates any additions or deletions of tags for the given product
	 *
	 * @param  Product $product Product object to update
	 *
	 * @return Product          Saved Product object
	 */
	public function saveTags(Product $product)
	{
		$options = array();
		$inserts = array();

		if (!$product->tags) {
			return $product;
		}

		$this->_parseTags($product);

		foreach ($product->tags as $tag) {
			$options[] = $product->id;
			$options[] = trim($tag);
			$inserts[] = '(?i,?s)';
		}

		// Delete any tags associated with this product
		$this->_trans->add(
			'DELETE FROM
				product_tag
			WHERE
				product_id = ?i',
			array(
				$product->id
			)
		);

		// Insert all the tags
		$this->_trans->add(
			'INSERT INTO
				product_tag
				(
					product_id,
					name
				)
			VALUES
				'.implode(',',$inserts).' ',
			$options
		);

		$this->_trans->commit();

		return $product;
	}

	/**
	 * Update the prices for the product
	 *
	 * @param  Product $product Product object to update
	 *
	 * @return Product          Saved Product object
	 */
	public function savePrices(Product $product)
	{
		$options = array();
		$inserts = array();

		foreach ($product->price as $type => $price) {
			$options[] = $product->id;
			$options[] = $type;
			$options[] = $product->price[$type]->getPrice('GBP', $this->_locale);
			$options[] = 'GBP';
			$options[] = $this->_locale->getID();
			$inserts[] = '(?i,?s,?s,?s,?s)';
		}

		$this->_trans->add(
			'REPLACE INTO
				product_price
				(
					product_id,
					type,
					price,
					currency_id,
					locale
				)
			VALUES
				'.implode(',',$inserts).' ',
			$options
		);

		$this->_trans->commit();

		return $product;
	}

	protected function _saveProduct()
	{
		if (!$this->_product) {
			throw new \LogicException('Cannot edit product as no product is set');
		}

		$this->_trans->add("
			UPDATE
				product
		 	SET
				updated_at   = :updatedAt?d,
				updated_by   = :updatedBy?in,
				name         = :name?s,
				tax_rate     = :taxRate?sn,
				tax_strategy = :taxStrategy?s,
				weight_grams = :weightGrams?in,
				category     = :category?sn
			WHERE
				product_id = :productID?i
			", array(
				'productID'         => $this->_product->id,
				'updatedAt'         => $this->_product->authorship->updatedAt(),
				'updatedBy'         => $this->_product->authorship->updatedBy()->id,
				'name'              => $this->_product->name,
				'taxRate'           => $this->_product->taxRate,
				'taxStrategy'       => $this->_product->taxStrategy,
				'weightGrams'       => $this->_product->weight,
				'category'          => $this->_product->category,
		));

		return $this;
	}

	/**
	 * If a product_info row does not exist, add a new one, else update it
	 *
	 * @throws \LogicException
	 *
	 * @return Edit                 Return $this for chainability
	 */
	protected function _saveProductInfo()
	{
		if (!$this->_product) {
			throw new \LogicException('Cannot edit product info as no product is set');
		}

		$this->_trans->add("
			INSERT INTO
				product_info
				(
					product_id,
					locale,
					display_name,
					description,
					short_description,
					notes
				)
			VALUES
				(
					:product_id?i,
					:locale?sn,
					:displayName?sn,
					:description?sn,
					:shortDescription?sn,
					:notes?sn
				)
			ON DUPLICATE KEY UPDATE
				display_name		= :displayName?sn,
				description			= :description?sn,
				short_description	= :shortDescription?sn,
				notes				= :notes?sn
		", array(
			'product_id'        => $this->_product->id,
			'locale'            => $this->_locale->getID(),
			'displayName'       => $this->_product->displayName,
			'description'       => $this->_product->description,
			'shortDescription'  => $this->_product->shortDescription,
			'notes'             => $this->_product->notes,
		));

		return $this;
	}

	/**
	 * Save data to product export table, create new row if not exists
	 *
	 * @throws \LogicException
	 *
	 * @return Edit
	 */
	protected function _saveProductExport()
	{
		if (!$this->_product) {
			throw new \LogicException('Cannot edit product export info as no product is set');
		}

		$this->_trans->add("
			INSERT INTO
				product_export
				(
					product_id,
					locale,
					export_value,
					export_description,
					export_manufacture_country_id
				)
			VALUES
				(
					:productID?i,
					:locale?sn,
					:exportValue?fn,
					:exportDescription?sn,
					:exportCountryID?s
				)
			ON DUPLICATE KEY UPDATE
				export_value					= :exportValue?fn,
				export_description				= :exportDescription?sn,
				export_manufacture_country_id	= :exportCountryID?s
		", array(
			'productID'         => $this->_product->id,
			'locale'            => $this->_locale->getID(),
			'exportValue'		=> $this->_product->exportValue,
			'exportDescription'	=> $this->_product->exportDescription,
			'exportCountryID'	=> $this->_product->exportManufactureCountryID,
		));

		return $this;
	}

	protected function _parseTags(Product $product)
	{
		if (!$product->tags) {
			return $product;
		}

		if (!is_array($product->tags) && (!$product->tags instanceof \Traversable) && !is_string($product->tags)) {
			throw new \LogicException('Product tags must be a traversable or a string');
		}

		if (is_string($product->tags)) {
			$tags = explode(',', $product->tags);
		}

		foreach ($tags as &$tag) {
			trim($tag);
		}

		$product->tags	= $tags;

		return $product;
	}
}