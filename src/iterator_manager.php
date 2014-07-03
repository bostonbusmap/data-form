<?php

require_once "paginator.php";
require_once "array_manager.php";
class IteratorManager implements IPaginator {
	/**
	 * @var Iterator
	 */
	protected $iterator;

	/**
	 * @var IPaginationInfo
	 */
	protected $pagination_info;

	/**
	 * @var bool
	 */
	protected $ignore_pagination;

	/**
	 * @var bool
	 */
	protected $ignore_filtering;

	public function __construct($iterator) {
		if (!($iterator instanceof Iterator)) {
			throw new Exception("iterator must be Iterator");
		}
		$this->iterator = $iterator;
	}

	public function pagination_info($pagination_info)
	{
		$this->pagination_info = $pagination_info;
	}

	public function ignore_pagination($ignore_pagination = true)
	{
		$this->ignore_pagination = $ignore_pagination;
	}

	public function ignore_filtering($ignore_filtering = true)
	{
		$this->ignore_filtering = $ignore_filtering;
	}

	public function obtain_paginated_data_and_row_count($conn_type, $rowid_key)
	{
		$iterator = $this->iterator;
		if (!$this->ignore_filtering && $this->pagination_info->has_search()) {
			$iterator = new RowFilterIterator($iterator, $this->pagination_info);
		}
		if ($this->pagination_info->has_sorting()) {
			// need to convert iterator to array to sort it
			$array = iterator_to_array($iterator);
			$array = ArrayManager::sort($array, $this->pagination_info);
			$iterator = new ArrayIterator($array);
		}

		if ($iterator instanceof Countable) {
			$num_rows = count($iterator);
		}
		else
		{
			$num_rows = null;
		}

		if (!$this->ignore_pagination) {
			if ($this->pagination_info->get_limit() === 0) {
				$limit_count = -1;
			}
			else
			{
				$limit_count = $this->pagination_info->get_limit();
			}
			try {
				$iterator = new LimitIterator($iterator, $this->pagination_info->get_offset(), $limit_count);
			}
			catch (OutOfBoundsException $e) {
				$iterator = new EmptyIterator();
			}
		}
		if ($rowid_key !== null) {
			$iterator = new ColumnAsKeyIterator($iterator, $rowid_key);
		}
		return array($iterator, $num_rows);
	}
}

class RowFilterIterator extends FilterIterator
{
	/**
	 * @var PaginationInfo
	 */
	protected $pagination_info;

	public function __construct($iterator, $pagination_info) {
		if (!($iterator instanceof Iterator)) {
			throw new Exception("iterator must be Iterator");
		}
		if (!($pagination_info instanceof IPaginationInfo)) {
			throw new Exception("pagination_info must be PaginationInfo");
		}

		parent::__construct($iterator);

		$this->pagination_info = $pagination_info;
	}

	public function accept()
	{
		return ArrayManager::accept($this->current(), $this->pagination_info);
	}
}