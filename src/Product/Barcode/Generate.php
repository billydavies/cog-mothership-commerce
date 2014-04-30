<?php

namespace Message\Mothership\Commerce\Product\Barcode;

use Message\Cog\DB\Query;
use Message\Cog\Filesystem\File;

/**
 * Class Generate
 * @package Message\Mothership\Commerce\Product\Barcode
 * @author Thomas Marchant <thomas@message.co.uk>
 */
class Generate
{
	const BARCODE_LOCATION = 'barcodes';
	const PUBLIC_PATH      = 'cog://public';

	/**
	 * @var \Message\Cog\DB\Query
	 */
	protected $_query;

	/**
	 * @var Image
	 */
	protected $_image;

	protected $_height      = 60;
	protected $_width       = 1;
	protected $_fileExt     = 'png';
	protected $_barcodeType = 'code39';

	protected $_supportedImageTypes = [
		'png',
		'jpg',
		'jpeg',
		'gif',
	];

	public function __construct(Query $query, Image $image, $height, $width, $fileExt, $type)
	{
		$this->_query   = $query;
		$this->_image  = $image;

		if ($height) {
			$this->setHeight($height);
		}

		if ($width) {
			$this->setWidth($width);
		}

		if ($fileExt) {
			$this->setFileExt($fileExt);
		}

		if ($type) {
			$this->setBarcodeType($type);
		}
	}

	public function getOneOfEach()
	{
		return $this->_getBarcodes();
	}

	/**
	 * @return int
	 */
	public function getHeight()
	{
		return $this->_height;
	}

	/**
	 * @param $height int | float | string
	 * @throws \InvalidArgumentException
	 *
	 * @return Generate
	 */
	public function setHeight($height)
	{
		if (!is_numeric($height)) {
			throw new \InvalidArgumentException('$height must be a numeric value');
		}

		$this->_height = (int) $height;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getWidth()
	{
		return $this->_width;
	}

	/**
	 * @param $width int | float | string
	 * @throws \InvalidArgumentException
	 *
	 * @return Generate
	 */
	public function setWidth($width)
	{
		if (!is_numeric($width)) {
			throw new \InvalidArgumentException('$width must be a numeric value');
		}

		$this->_width = (int) $width;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getFileExt()
	{
		return $this->_fileExt;
	}

	/**
	 * @param $ext string
	 * @throws \InvalidArgumentException
	 *
	 * @return Generate
	 */
	public function setFileExt($ext)
	{
		if (!in_array($ext, $this->_supportedImageTypes)) {
			$error = (is_string($ext)) ?
				$ext . ' is not a supported file extension' :
				'$ext must be a string, ' . gettype($ext) . ' given';

			throw new \InvalidArgumentException($error);
		}

		$this->_fileExt = $ext;

		return $this;
	}

	/**
	 * @return string
	 */
	public function getBarcodeType()
	{
		return $this->_barcodeType;
	}

	/**
	 * @param $type
	 * @throws \InvalidArgumentException
	 *
	 * @return Generate
	 */
	public function setBarcodeType($type)
	{
		if (!is_string($type)) {
			throw new \InvalidArgumentException('$type must be a string, ' . gettype($type) . ' given');
		}

		$this->_barcodeType = $type;

		return $this;
	}

	/**
	 * This loads only the information we need, and assigns it to a Barcode object. As it stands the system does not
	 * load the products efficiently enough and will break if the load is too high, so this binds to a lightweight
	 * Barcode object to mitigate this problem, see https://github.com/messagedigital/cog-mothership-commerce/issues/297
	 *
	 * @param $unitIDs array                  Not currently used but will be useful when dealing with individual units
	 *
	 * @return \Message\Cog\DB\Result
	 */
	protected function _getBarcodes($unitIDs = [])
	{
		$barcodes = $this->_query->run("
			SELECT DISTINCT
				u.unit_id AS unitID,
				p.brand,
				p.name,
				u.barcode,
				up.price,
				up.currency_id AS currency,
				GROUP_CONCAT(o.option_value, ', ') AS text
			FROM
				product_unit AS u
			LEFT JOIN
				product AS p
			USING
				(product_id)
			LEFT JOIN
				product_unit_option AS o
			USING
				(unit_id)
			LEFT JOIN
				product_unit_price AS up
			USING
				(unit_id)
			WHERE
				barcode IS NOT NULL
			AND
				barcode != ''
			AND
				up.type = :retail?s
			GROUP BY
				u.unit_id
		", [
			'retail' => 'retail',
		])->bindTo('\\Message\\Mothership\\Commerce\\Product\\Barcode\\Barcode');

		foreach ($barcodes as $barcode) {
			$code          = $barcode->getBarcode();
			$barcode->text = trim($barcode->text, ' ,');
			$barcode->file = $this->_getBarcodeImage($code);
			$barcode->url  = $barcode->file->getPublicUrl() . '/' . $this->_getFilename($code);
		}

		return $barcodes;
	}

	/**
	 * Method to return the url of the barcode image file. If no image exists, it creates the image and saves it to
	 * the file system
	 *
	 * @param $barcode
	 *
	 * @return string       Returns location of the barcode image file
	 */
	protected function _getBarcodeImage($barcode)
	{
		if (!$this->_image->exists($this->_getFilename($barcode))) {
			$image = $this->_image->getImage(
				$barcode,
				$this->getBarcodeType(),
				$this->getFileExt(),
				$this->getHeight(),
				$this->getWidth()
			);

			$this->_image->save($image, $this->_getFilename($barcode), $this->getFileExt());
		}

		return $this->_image->getFile($this->_getFilename($barcode));
	}

	/**
	 * Returns a filename generated using a hash of the barcode and attributes such as the size and file type
	 *
	 * @param $barcode
	 *
	 * @return string
	 */
	protected function _getFilename($barcode)
	{
		$string = $barcode . $this->getFileExt() . $this->getHeight() .	$this->getWidth() . $this->getBarcodeType();

		return md5($string) . '.' . $this->getFileExt();
	}

}