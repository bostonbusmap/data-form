<?php

/**
 * Builds a PaginationInfo. You may want to use DataFormState::make_pagination_info() to create this object instead.
 */
class PaginationInfoBuilder {
	/**
	 * @var array ordered mapping of column_key => asc or desc
	 */
	protected $sorting_order;

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
		$this->sorting_order = array();
	}

	/**
	 * @param $column_key string
	 * @param $order string|null
	 * @return PaginationInfoBuilder
	 */
	public function set_sorting_order($column_key, $order) {
		$this->sorting_order[$column_key] = $order;
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

	public function get_sorting_order() {
		return $this->sorting_order;
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

		if (!is_array($this->sorting_order)) {
			throw new Exception("order expected to be an array");
		}
		foreach ($this->sorting_order as $k => $v) {
			if (!is_string($k) || trim($k) === "") {
				throw new Exception("column_key must be a string");
			}
			if ($v !== null && $v !== "") {
				$lower = strtolower($v);
				if ($lower !== PaginationInfo::sorting_state_desc &&
					$lower !== PaginationInfo::sorting_state_asc
				) {
					throw new Exception("Each value in order must be asc or desc");
				}
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

/**
 * PaginationInfo looks at default values from $settings
 * and user supplied values from $state and figures out the proper values
 * for pagination, filtering and sorting values.
 *
 * You may want to use DataFormState::make_pagination_info() to create this object
 */
class PaginationInfo {
	const sorting_state_desc = "desc";
	const sorting_state_asc = "asc";

	/**
	 * @var array ordered mapping of column_key => asc or desc
	 */
	protected $sorting_order;

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
	 * @param $builder PaginationInfoBuilder
	 * @throws Exception
	 */
	public function __construct($builder) {
		if (!($builder instanceof PaginationInfoBuilder)) {
			throw new Exception("builder must be a PaginationInfoBuilder");
		}

		$this->sorting_order = $builder->get_sorting_order();
		$this->limit = $builder->get_limit();
		$this->offset = $builder->get_offset();
		$this->search_states = $builder->get_search_states();
	}

	/**
	 * @return array
	 */
	public function get_sorting_order() {
		return $this->sorting_order;
	}

	/**
	 * @return array
	 */
	public function get_search_states() {
		return $this->search_states;
	}

	/**
	 * @return int
	 */
	public function get_limit() {
		return $this->limit;
	}

	/**
	 * @return int
	 */
	public function get_offset() {
		return $this->offset;
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
		if (!is_int($total_rows)) {
			throw new Exception("total_rows expected to be an int");
		}

		$num_pages = $this->calculate_num_pages($total_rows);

		if ($this->limit === 0) {
			return 0;
		}
		else {
			$current_page = $this->offset / $this->limit;
			if ($current_page >= $num_pages) {
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
	 * @param $num_rows int
	 * @throws Exception
	 * @return int
	 */
	public function calculate_num_pages($num_rows)
	{
		if (!is_int($num_rows)) {
			throw new Exception("num_rows must be an int");
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