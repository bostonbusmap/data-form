<?php

/**
 * Builds a PaginationInfo. You may want to use DataFormState::make_pagination_info() to create this object instead.
 */
class PaginationInfoBuilder {
	/**
	 * @var array ordered mapping of column_key => DataTableSortingState
	 */
	protected $sorting_states;

	/**
	 * @var int|null
	 */
	protected $limit;
	/**
	 * @var int|null
	 */
	protected $offset;

	/**
	 * @var array column_key -> a list of parameters
	 */
	protected $search_states;

	/**
	 * @return PaginationInfoBuilder
	 */
	public static function create() {
		return new PaginationInfoBuilder();
	}

	public function __construct() {
		$this->search_states = array();
		$this->sorting_states = array();
	}

	/**
	 * @param $column_key string
	 * @param $sorting_state DataTableSortingState
	 * @return PaginationInfoBuilder
	 */
	public function set_sorting_state($column_key, $sorting_state) {
		$this->sorting_states[$column_key] = $sorting_state;
		return $this;
	}

	/**
	 * @param $limit int
	 * @return PaginationInfoBuilder
	 */
	public function limit($limit) {
		$this->limit = $limit;
		return $this;
	}

	/**
	 * @param $offset int
	 * @return PaginationInfoBuilder
	 */
	public function offset($offset) {
		$this->offset = $offset;
		return $this;
	}

	/**
	 * @param $column_key string
	 * @param $search_state DataTableSearchState
	 * @return PaginationInfoBuilder
	 */
	public function set_search_state($column_key, $search_state) {
		$this->search_states[$column_key] = $search_state;
		return $this;
	}

	public function get_sorting_states() {
		return $this->sorting_states;
	}
	public function get_limit() {
		return $this->limit;
	}
	public function get_offset() {
		return $this->offset;
	}
	public function get_search_states() {
		return $this->search_states;
	}

	public function has_sorting() {
		if (!is_array($this->sorting_states)) {
			throw new Exception("order expected to be an array");
		}

		foreach ($this->sorting_states as $k => $v) {
			if ($v instanceof DataTableSortingState) {
				if ($v->is_active()) {
					return true;
				}
			}
		}
		return false;
	}

	public function has_search() {
		if (!is_array($this->search_states)) {
			throw new Exception("search_states expected to be an array");
		}
		foreach ($this->search_states as $k => $v) {
			if ($v instanceof DataTableSearchState) {
				if ($v->is_active()) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @return PaginationInfo
	 * @throws Exception
	 */
	public function build() {
		if (!is_int($this->limit)) {
			throw new Exception("limit must an integer");
		}
		if (!is_int($this->offset)) {
			throw new Exception("offset must an integer");
		}

		if (!is_array($this->sorting_states)) {
			throw new Exception("order expected to be an array");
		}
		foreach ($this->sorting_states as $k => $v) {
			if (!is_string($k) || trim($k) === "") {
				throw new Exception("column_key must be a string");
			}
			if ($v !== null && $v !== "" && !($v instanceof DataTableSortingState)) {
				throw new Exception("Each item must be instance of DataTableSortingState");
			}
		}
		if (!is_array($this->search_states)) {
			throw new Exception("search_states expected to be an array");
		}
		foreach ($this->search_states as $k => $v) {
			if (!is_string($k) || trim($k) === "") {
				throw new Exception("column_key must be a string");
			}
			if ($v !== null && $v !== "" && !($v instanceof DataTableSearchState)) {
				throw new Exception("Each value must be instance of DataTableSearchState");
			}
		}

		return new PaginationInfo($this);
	}
}

interface IPaginationInfo {

	const sorting_state_desc = DataTableSortingState::sort_order_desc;
	const sorting_state_asc = DataTableSortingState::sort_order_asc;

	/**
	 * @return array
	 */
	public function get_sorting_states();

	/**
	 * @return array
	 */
	public function get_search_states();

	/**
	 * @return bool True if any sorting is specified
	 */
	public function has_sorting();

	/**
	 * @return bool True if any filtering is specified
	 */
	public function has_search();

	/**
	 * @return int
	 */
	public function get_limit();

	/**
	 * @return int
	 */
	public function get_offset();

	/**
	 * Calculate current page number (0-based).
	 *
	 * @param $total_rows int The number of rows in the data set
	 * @throws Exception
	 * @return int The current page (starting from 0). If $settings is specified and page is past the end, return the last valid page
	 */
	public function calculate_current_page($total_rows);

	/**
	 * Return number of pages given the row count and number of rows per page
	 *
	 * @param $num_rows int|null
	 * @throws Exception
	 * @return int|null
	 */
	public function calculate_num_pages($num_rows);
}

/**
 * PaginationInfo looks at default values from $settings
 * and user supplied values from $state and figures out the proper values
 * for pagination, filtering and sorting values.
 *
 * You may want to use DataFormState::make_pagination_info() to create this object
 */
class PaginationInfo implements IPaginationInfo
{

	/**
	 * @var array ordered mapping of column_key => DataTableSortingState
	 */
	protected $sorting_states;

	/**
	 * @var int Number of rows per page
	 */
	protected $limit;
	/**
	 * @var int Row number to start at (starting from zero)
	 */
	protected $offset;

	/**
	 * @var array column_key -> DataTableSearchState. Specify a search state for a particular column
	 */
	protected $search_states;


	/**
	 * @var bool
	 */
	protected $has_sorting;

	/**
	 * @var bool
	 */
	protected $has_search;

	/**
	 * @param $builder PaginationInfoBuilder
	 * @throws Exception
	 */
	public function __construct($builder)
	{
		if (!($builder instanceof PaginationInfoBuilder)) {
			throw new Exception("builder must be a PaginationInfoBuilder");
		}

		$this->sorting_states = $builder->get_sorting_states();
		$this->limit = $builder->get_limit();
		$this->offset = $builder->get_offset();
		$this->search_states = $builder->get_search_states();
		$this->has_search = $builder->has_search();
		$this->has_sorting = $builder->has_sorting();
	}

	public function get_sorting_states()
	{
		return $this->sorting_states;
	}

	/**
	 * @return array
	 */
	public function get_search_states()
	{
		return $this->search_states;
	}

	/**
	 * @return int
	 */
	public function get_limit()
	{
		return $this->limit;
	}

	/**
	 * @return int
	 */
	public function get_offset()
	{
		return $this->offset;
	}

	public function has_sorting() {
		return $this->has_sorting;
	}

	public function has_search() {
		return $this->has_search;
	}

	/**
	 * Calculate current page number (0-based).
	 *
	 * @param $total_rows int The number of rows in the data set
	 * @throws Exception
	 * @return int The current page (starting from 0). If $settings is specified and page is past the end, return the last valid page
	 */
	public function calculate_current_page($total_rows)
	{
		if ($total_rows !== null && !is_int($total_rows)) {
			throw new Exception("total_rows expected to be an int");
		}

		$num_pages = $this->calculate_num_pages($total_rows);

		if ($this->limit === 0) {
			return 0;
		} else {
			$current_page = $this->offset / $this->limit;
			if ($num_pages !== null && $current_page >= $num_pages) {
				if ($num_pages > 1) {
					return $num_pages - 1;
				} else {
					return 0;
				}
			} else {
				return $current_page;
			}
		}
	}

	/**
	 * Return number of pages given the row count and number of rows per page
	 *
	 * @param $num_rows int|null
	 * @throws Exception
	 * @return int|null
	 */
	public function calculate_num_pages($num_rows)
	{
		if ($num_rows === null) {
			return null;
		} elseif (!is_int($num_rows)) {
			throw new Exception("num_rows must be an int or null");
		}

		$limit = $this->get_limit();
		if ($limit === 0) {
			$num_pages = 1;
		} elseif (($num_rows % $limit) !== 0) {
			$num_pages = (int)(($num_rows / $limit) + 1);
		} else {
			$num_pages = (int)($num_rows / $limit);
		}
		return $num_pages;
	}
}

class PaginationInfoWithCount implements IPaginationInfo {
	/**
	 * @var int
	 */
	protected $row_count;
	/**
	 * @var PaginationInfo
	 */
	protected $pagination_info;

	/**
	 * @param $pagination_info IPaginationInfo
	 * @param $row_count int
	 * @throws Exception
	 */
	public function __construct($pagination_info, $row_count) {
		if (!($pagination_info instanceof IPaginationInfo)) {
			throw new Exception("pagination_info must be instance of IPaginationInfo");
		}
		if (!is_int($row_count)) {
			throw new Exception("row_count must be an int");
		}
		$this->pagination_info = $pagination_info;
		$this->row_count = $row_count;
	}

	public function get_row_count() {
		return $this->row_count;
	}

	public function get_sorting_states()
	{
		return $this->pagination_info->get_sorting_states();
	}

	public function get_search_states()
	{
		return $this->pagination_info->get_search_states();
	}

	public function get_limit()
	{
		return $this->pagination_info->get_limit();
	}

	public function get_offset()
	{
		return $this->pagination_info->get_offset();
	}

	public function calculate_current_page($total_rows)
	{
		return $this->pagination_info->calculate_current_page($total_rows);
	}

	public function calculate_num_pages($num_rows)
	{
		return $this->pagination_info->calculate_num_pages($num_rows);
	}

	public function has_sorting()
	{
		return $this->pagination_info->has_sorting();
	}

	public function has_search()
	{
		return $this->pagination_info->has_search();
	}
}