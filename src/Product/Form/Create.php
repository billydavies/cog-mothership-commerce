<?php

namespace Message\Mothership\Commerce\Product\Form;

use Message\Cog\Localisation\Translator;

use Message\Mothership\Commerce\Form\Extension\Type\ProductType;
use Symfony\Component\Form;
use Message\Mothership\Commerce\Product\Form\DataTransform\ProductTransform;
use Message\Mothership\Commerce\Product\Type\Collection as ProductTypeCollection;

class Create extends ProductType
{
	/**
	 * @var \Message\Cog\Localisation\Translator
	 */
	protected $_transformer;

	public function __construct(Translator $trans, ProductTypeCollection $productTypes, ProductTransform $transformer)
	{
		parent::__construct($trans, $productTypes);

		$this->_transformer = $transformer;
	}

	public function getName()
	{
		return 'product_create';
	}

	public function buildForm(Form\FormBuilderInterface $builder, array $options)
	{
		
		parent::buildForm($builder, $options);

		$builder->addModelTransformer($this->_transformer);

		return $this;
	}
}