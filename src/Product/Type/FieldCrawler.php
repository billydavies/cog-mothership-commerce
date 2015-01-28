<?php

namespace Message\Mothership\Commerce\Product\Type;

use Message\Cog\Field;
use Message\Mothership\Commerce\Product\Type;

use Symfony\Component\Validator\Constraints;

/**
 * Class to extract the fields from a product type.
 *
 * @todo annoyingly in order to access the fields, this needs to extend the `Message\Cog\Field\Factory` class and
 * run `setFields()` on the types. This is quite blatantly a hack and inherently unstable as if the `$_fields` property
 * became private, this method would break. If anyone has any ideas of how to approach this issue please let me know
 *
 * Class FieldCrawler
 * @package Message\Mothership\Commerce\Product\Type
 *
 * @author Thomas Marchant <thomas@message.co.uk>
 */
class FieldCrawler extends Field\Factory
{
	const CONSTRAINT_OPTION = 'constraints';

	/**
	 * @var \Message\Mothership\Commerce\Product\Type\Collection
	 */
	private $_types;

	/**
	 * @var array
	 */
	private $_fieldNames = [];

	/**
	 * @var array
	 */
	private $_fieldDescriptions = [];

	private $_productTypeFields = [];

	private $_constraints    = [];
	private $_constraintsSet = false;

	public function __construct(Type\Collection $types)
	{
		$this->_types = $types;
	}

	/**
	 * Method for extracting names of fields from a product type
	 */
	public function getFieldNames()
	{
		if (empty($this->_fieldNames)) {
			$this->_setFieldNames();
		}

		return $this->_fieldNames;
	}

	public function getTypeFields()
	{
		if (!$this->_productTypeFields) {
			$this->_setFields();
		}

		return $this->_productTypeFields;
	}

	public function getFieldDescriptions()
	{
		if (empty($this->_fieldDescriptions)) {
			$this->_setFieldDescriptions();
		}

		return $this->_fieldDescriptions;
	}

	public function getConstraints($fieldName = null)
	{
		if (!$this->_constraintsSet) {
			$this->_setFields();
		}

		if ($fieldName) {
			return (array_key_exists($fieldName, $this->_constraints)) ? $this->_constraints[$fieldName] : [];
		}

		return $this->_constraints;
	}

	private function _setFields()
	{
		$this->clear();

		foreach ($this->_types as $type) {
			$this->_setProductTypeFields($type);
		}

		$this->_setConstraints();
	}

	/**
	 * Loop through the product types and set the fields
	 */
	private function _setFieldNames()
	{
		if (empty($this->_fields)) {
			$this->_setFields();
		}

		$this->_fieldNames = $this->_mapFieldNames();
	}

	private function _setFieldDescriptions()
	{
		if (empty($this->_fields)) {
			$this->_setFields();
		}

		$this->_mapFieldDescriptions();
	}

	/**
	 * Set fields on each
	 *
	 * @param Type\ProductTypeInterface $type
	 */
	private function _setProductTypeFields(Type\ProductTypeInterface $type)
	{
		$existingFields = array_values($this->_fields);

		$type->setFields($this);
		$diff = array_diff($this->_fields, $existingFields);
		$this->_productTypeFields[$type->getName()] = $this->_mapFieldNames($diff);
	}

	/**
	 * Convert array of field objects into an array of strings
	 */
	private function _mapFieldNames(array $fields = null)
	{
		$fieldNames = $fields ?: $this->_fields;

		array_walk($fieldNames, function (&$field) {
			$field = $field->getLabel() ?: ucfirst($field->getName());
		});

		return $fieldNames;
	}

	private function _mapFieldDescriptions()
	{
		$descriptions = $this->_fields;

		array_walk($descriptions, function (&$field) {
			$field = $field->getDescription();
		});
	}

	private function _setConstraints()
	{
		foreach ($this->_fields as $field) {
			$this->_validateField($field);
			$options = $field->getFieldOptions();
			$this->_constraints[$field->getName()] = array_key_exists('constraints', $options) ? $options['constraints'] : [];
		}

		$this->_constraintsSet = true;
	}

	private function _validateField($field)
	{
		if (!$field instanceof Field\BaseField) {
			throw new \LogicException('Field must be an instance of `Message\Cog\Field\BaseField`, ' . gettype($field) . ' given');
		}
	}
}