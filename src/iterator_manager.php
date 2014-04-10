<?php

require_once "paginator.php";
require_once "array_manager.php";
class IteratorManager implements IPaginator {
	/**
	 * @var Iterator
	 */
	protected $iterator;
	/**
	 * @var string
	 */
	protected $table_name;

	/**
	 * @var DataFormState
	 */
	protected $state;

	/**
	 * @var DataTableSettings
	 */
	protected $settings;

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

	public function table_name($table_name)
	{
		$this->table_name = $table_name;
	}

	public function state($state)
	{
		$this->state = $state;
	}

	public function settings($settings)
	{
		$this->settings = $settings;
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
		$settings = $this->settings;
		$pagination_info = DataFormState::make_pagination_info($this->state, $settings, $this->table_name);

		$iterator = $this->iterator;
		if (!$this->ignore_filtering) {
			$iterator = new RowFilterIterator($iterator, $pagination_info);
		}
		if (!$this->ignore_pagination) {
			$iterator = new LimitIterator($iterator, $pagination_info->get_offset(), $pagination_info->get_limit());
		}
		if ($rowid_key !== null) {
			$iterator = new ColumnAsKeyIterator($iterator, $rowid_key);
		}
		return array($iterator, null);
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
		if (!($pagination_info instanceof PaginationInfo)) {
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