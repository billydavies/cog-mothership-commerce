<?php

namespace Message\Mothership\Commerce\Product\Image;

use Message\Cog\DB;
use Message\User\UserInterface;

class Delete implements DB\TransactionalInterface
{
	protected $_trans;
	protected $_currentUser;

	protected $_transOverridden = false;

	/**
	 * Constructor
	 * 
	 * @param DBTransaction $trans
	 * @param UserInterface $currentUser
	 */
	public function __construct(DB\Transaction $trans, UserInterface $currentUser)
	{
		$this->_trans       = $trans;
		$this->_currentUser = $currentUser;
	}

	/**
	 * {@inheritdoc}
	 */
	public function setTransaction (DB\Transaction $trans)
	{
		$this->_trans           = $trans;
		$this->_transOverridden = true;
	}

	/**
	 * Deletes the given image
	 * 
	 * @param  Image  $image
	 */
	public function delete (Image $image)
	{
		$image->authorship->delete();

		$this->_trans->add('
			DELETE FROM
				`product_image`
			WHERE
				`image_id` = ?s
			', 
			[ $image->id, ]
			);

		$this->_trans->add('
			DELETE FROM
				`product_image_option`
			WHERE
				`image_id` = ?s
			',
			[ $image->id, ]
			);
		
		if (!$this->_transOverridden) {
			$this->_trans->commit();
		}
	}

	/**
	 * Deletes all images in the array of images
	 * @param  array $images array of Images to delete
	 */
	public function deleteMulti ($images)
	{
		$ids = [];
		foreach ($images as $image) {
			$ids[] = $image->id;
		}

		$this->_trans->add(
			"DELETE FROM
				`product_image`
			WHERE
				`image_id` IN (?js)
			", [$ids]);

		$this->_trans->add(
			"DELETE FROM
				`product_image_option`
			WHERE
				`image_id` IN (?js)
			", [$ids]);

		if (!$this->_transOverridden) {
			$this->_trans->commit();
		}
	}
}