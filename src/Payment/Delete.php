<?php

namespace Message\Mothership\Commerce\Payment;

use Message\Cog\ValueObject\DateTimeImmutable;

use Message\Cog\DB;
use Message\User\UserInterface;

/**
 * Decorator for deleting payments.
 */
class Delete implements DB\TransactionalInterface
{
	protected $_query;
	protected $_currentUser;

	/**
	 * Constructor.
	 *
	 * @param DB\Query      $query       The database query instance to use
	 * @param UserInterface $currentUser The currently logged in user
	 */
	public function __construct(DB\Query $query, UserInterface $user)
	{
		$this->_query           = $query;
		$this->_currentUser     = $user;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setTransaction(DB\Transaction $transaction)
	{
		$this->_query = $transaction;
	}

	/**
	 * Delete a payment by marking it as deleted in the database.
	 *
	 * @param  Payment $payment The payment to be deleted
	 *
	 * @return Payment          The payment that was deleted, with the "delete"
	 *                          authorship data set
	 */
	public function delete(Payment $payment)
	{
		$payment->authorship->delete(new DateTimeImmutable, $this->_currentUser->id);

		$this->_query->run('
			UPDATE
				payment
			SET
				deleted_at = :at?d,
				deleted_by = :by?in
			WHERE
				payment_id = :id?i
		', array(
			'at' => $payment->authorship->deletedAt(),
			'by' => $payment->authorship->deletedBy(),
			'id' => $payment->id,
		));

		return $payment;
	}
}