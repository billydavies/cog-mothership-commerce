<?php

namespace Message\Mothership\Commerce\Form\Extension\Type;

use Message\Cog\Localisation\Translator;

use Message\Mothership\Commerce\Form\Extension\Type\UnitType;
use Symfony\Component\Form;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Message\Mothership\Commerce\Product\Form\DataTransform\ProductTransform;

class ProductType extends Form\AbstractType
{
	/**
	 * @var \Message\Cog\Localisation\Translator
	 */
	protected $_trans;

	public function __construct(Translator $trans)
	{
		$this->_trans       = $trans;
	}

	public function getName()
	{
		return 'product';
	}

	public function buildForm(Form\FormBuilderInterface $builder, array $options)
	{
		$builder->add('name', 'text', [
				'label' => 'ms.commerce.product.create.name.label',
				'attr'  => [
					'placeholder' => $this->_trans->trans('ms.commerce.product.create.name.placeholder'),
				],
				'constraints' => [ new Constraints\NotBlank, ],
			])
			->add('brand', 'text', [
				'label' => 'ms.commerce.product.create.brand.label',
				'attr'  => [
					'placeholder' => $this->_trans->trans('ms.commerce.product.create.brand.placeholder'),
				],
				'constraints' => [ new Constraints\NotBlank, ],
			])
			->add('category', 'text', [
				'label' => 'ms.commerce.product.create.category.label',
				'attr'  => [
					'placeholder' => $this->_trans->trans('ms.commerce.product.create.category.placeholder'),
				],
				'constraints' => [ new Constraints\NotBlank, ],
			])
			->add('units', 'collection', [
				'type'      => 'product_unit',
				'allow_add' => true,
				'prototype_name' => '__unit__',
			])
			->add('price', 'money', [
				'label' => 'ms.commerce.product.create.price.label',
				'attr'  => [
					'placeholder' => $this->_trans->trans('ms.commerce.product.create.price.placeholder'),
					'currency' => 'GBP',
				],
				'constraints' => [ new Constraints\NotBlank, ],
			])
			->add('short_description', 'textarea', [
				'label' => 'ms.commerce.product.create.description.label',
				'attr'  => [
					'placeholder' => $this->_trans->trans('ms.commerce.product.create.description.placeholder'),
				],
				'constraints' => [ new Constraints\NotBlank, ],
			])
		;

		return $this;
	}
}