<?php

namespace Message\Mothership\Commerce\User\Address;

use Message\User\User;
use Message\Cog\DB\Query;
use Message\Cog\ValueObject\DateTimeImmutable;

class Create
{
	protected $_query;
	protected $_user;

	public function __construct(Query $query, User $user, Loader $loader)
	{
		$this->_query  = $query;
		$this->_user   = $user;
		$this->_loader = $loader;
	}

	public function save(Address $address)
	{
		$date = new DateTimeImmutable;
		$result = $this->_query->run(
			'INSERT INTO
				user_address
			SET
				name       = :name?s,
				line_1     = :line_1?sn,
				line_2     = :line_2?sn,
				line_3     = :line_3?sn,
				line_4     = :line_4?sn,
				town       = :town?s,
				state_id   = :state_id?s,
				country_id = :country_id?s,
				postcode   = :postcode?s,
				telephone  = :telephone?s,
				created_at = :created_at?d,
				created_by = :created_by?i,
				user_id    = :userID?i
			', array(
				'addressID'  => $address->id,
				'name'       => $address->name,
				'line_1'     => $address->lines[1],
				'line_2'     => $address->lines[2],
				'line_3'     => $address->lines[3],
				'line_4'     => $address->lines[4],
				'town'       => $address->town,
				'state_id'   => $address->stateID,
				'country_id' => $address->countryID,
				'postcode'   => $address->postcode,
				'telephone'  => $address->telephone,
				'created_at' => $date,
				'created_by' => $this->_user->id,
				'userID'     => $address->userID,
			)
		);

		return $address;
	}
}