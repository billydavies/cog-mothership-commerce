<?php

namespace Message\Mothership\Commerce\Order\Status;

/**
 * A container for order statuses that are available throughout the system.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class Collection implements \IteratorAggregate, \Countable
{
	protected $_statuses = array();

	/**
	 * Constructor.
	 *
	 * @param array $statuses An array of statuses to add
	 */
	public function __construct(array $statuses = array())
	{
		foreach ($statuses as $status) {
			$this->add($status);
		}
	}

	/**
	 * Add a status to this collection.
	 *
	 * The statuses on this collection are sorted by code ascending immediately
	 * after the new status is added.
	 *
	 * @param Status $status The status to add
	 *
	 * @return Collection    Returns $this for chainability
	 *
	 * @throws \InvalidArgumentException If the status has no code set
	 * @throws \InvalidArgumentException If a status with the same code has
	 *                                   already been set on this collection
	 */
	public function add(Status $status)
	{
		if (!$status->code && 0 !== $status->code) {
			throw new \InvalidArgumentException(sprintf('Status `%s` has no code', $status->name));
		}

		if ($this->exists($status->code)) {
			throw new \InvalidArgumentException(sprintf(
				'Status code `%d is already defined as `%s`',
				$status->code,
				$this->_statuses[$status->code]->name
			));
		}

		$this->_statuses[$status->code] = $status;

		ksort($this->_statuses);

		return $this;
	}

	/**
	 * Get a status set on this collection by the code.
	 *
	 * @param  int $code      The status code
	 *
	 * @return GroupInterface The group instance
	 *
	 * @throws \InvalidArgumentException If the status has not been set
	 */
	public function get($code)
	{
		if (!$this->exists($code)) {
			throw new \InvalidArgumentException(sprintf('Status code `%s` not set on collection', $code));
		}

		return $this->_statuses[$code];
	}

	/**
	 * Get all statuses set on this collection, where the keys are the status
	 * codes.
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->_statuses;
	}

	/**
	 * Check if a given status code has been defined on this collection.
	 *
	 * @param  int $code The status code
	 *
	 * @return boolean   True if it exists, false otherwise
	 */
	public function exists($code)
	{
		return array_key_exists($code, $this->_statuses);
	}

	/**
	 * Get the number of statuses registered on this collection.
	 *
	 * @return int The number of statuses registered
	 */
	public function count()
	{
		return count($this->_statuses);
	}

	/**
	 * Get the iterator object to use for iterating over this class.
	 *
	 * @return \ArrayIterator An \ArrayIterator instance for the `_statuses`
	 *                        property
	 */
	public function getIterator()
	{
		return new \ArrayIterator($this->_statuses);
	}
}