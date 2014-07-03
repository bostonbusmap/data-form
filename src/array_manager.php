<?php
/**
 * Use sorting, pagination and filtering information to provide an altered 2D array
 * given pagination state and settings
 *
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once "paginator.php";

/**
 * Use sorting, pagination and filtering information to provide an altered 2D array
 * given pagination state and settings
 */
class ArrayManager implements IPaginator {
	/** @var  array */
	protected $array;
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

	/**
	 * @param $array array
	 * @throws Exception
	 */
	public function __construct($array) {
		if (!is_array($array)) {
			throw new Exception("array must be an array");
		}
		$this->array = $array;
	}

	/**
	 * Same as constructor, allows chaining of method calls
	 *
	 * @param $array array
	 * @return ArrayManager
	 */
	public static function create($array) {
		return new ArrayManager($array);
	}

	public function pagination_info($pagination_info) {
		$this->pagination_info = $pagination_info;
		return $this;
	}

	public function ignore_pagination($ignore_pagination = true) {
		$this->ignore_pagination = $ignore_pagination;
		return $this;
	}

	public function ignore_filtering($ignore_filtering = true) {
		$this->ignore_filtering = $ignore_filtering;
		return $this;
	}

	public function validate_input() {
		if (!is_array($this->array)) {
			throw new Exception("array must be an array");
		}
		if ($this->ignore_pagination === null) {
			$this->ignore_pagination = false;
		}
		if (!is_bool($this->ignore_pagination)) {
			throw new Exception("ignore_pagination must be a bool");
		}

		if ($this->ignore_filtering === null) {
			$this->ignore_filtering = false;
		}
		if (!is_bool($this->ignore_filtering)) {
			throw new Exception("ignore_filtering must be a bool");
		}

		if (!($this->pagination_info instanceof IPaginationInfo)) {
			throw new Exception("pagination_info must be instance of IPaginationInfo");
		}
	}
	// for backwards compat
	public function make_filtered_subset() {
		list($array, $num_rows) = $this->obtain_paginated_data_and_row_count(null, null);
		return array($num_rows, $array);
	}

	public function obtain_paginated_data_and_row_count($conn_type, $rowid_key) {
		$this->validate_input();

		$array = $this->array;

		$pagination_info = $this->pagination_info;
		// Order is important here
		if (!$this->ignore_filtering) {
			$array = self::filter($array, $pagination_info);
		}
		$num_rows = count($array);
		$array = self::sort($array, $pagination_info);
		if (!$this->ignore_pagination) {
			$array = self::paginate($array, $pagination_info, $num_rows);
		}

		$iterator = new ArrayIterator($array);
		if ($rowid_key !== null) {
			$iterator = new ColumnAsKeyIterator($iterator, $rowid_key);
		}
		return array($iterator, $num_rows);
	}

	/**
	 * @param $row array
	 * @param $pagination_info IPaginationInfo
	 * @return bool
	 * @throws Exception
	 */
	public static function accept($row, $pagination_info) {
		if (!is_array($row)) {
			throw new Exception("row must be an array");
		}
		if (!($pagination_info instanceof IPaginationInfo)) {
			throw new Exception("pagination_info must be a PaginationInfo");
		}
		$searching_state = $pagination_info->get_search_states();

		foreach ($row as $column_key => $cell) {
			if (!array_key_exists($column_key, $searching_state)) {
				continue;
			}
			/** @var $obj DataTableSearchState */
			$obj = $searching_state[$column_key];
			if ($obj === null) {
				continue;
			}
			$params = $obj->get_params();
			$type = $obj->get_type();
			if ($type === DataTableSearchState::like ||
				$type === DataTableSearchState::rlike ||
				$type === DataTableSearchState::less_than ||
				$type === DataTableSearchState::less_or_equal ||
				$type === DataTableSearchState::greater_than ||
				$type === DataTableSearchState::greater_or_equal ||
				$type === DataTableSearchState::equal ||
				$type === DataTableSearchState::in ||
				$type === DataTableSearchState::not_equal
			) {
				$value = $params[0];
				// TODO: check is_numeric for numeric comparisons
				if ($value !== "") {
					if ($type === DataTableSearchState::like) {
						if (stripos($cell, $value) === false) {
							return false;
						}
					} elseif ($type === DataTableSearchState::rlike) {
						$escaped_value = str_replace("\\", "\\\\", $value);
						$escaped_value = str_replace("/", "\\/", $escaped_value);
						if (preg_match('/' . $escaped_value . '/i', $cell) !== 1) {
							return false;
						}
					} elseif ($type === DataTableSearchState::less_than) {
						if ($cell >= $value) {
							return false;
						}
					} elseif ($type === DataTableSearchState::less_or_equal) {
						if ($cell > $value) {
							return false;
						}
					} elseif ($type === DataTableSearchState::greater_than) {
						if ($cell <= $value) {
							return false;
						}
					} elseif ($type === DataTableSearchState::greater_or_equal) {
						if ($cell < $value) {
							return false;
						}
					} elseif ($type === DataTableSearchState::equal) {
						if ($cell != $value) {
							return false;
						}
					} elseif ($type === DataTableSearchState::in) {
						$pieces = explode(",", $value);
						$in = false;
						foreach ($pieces as $piece) {
							if ($cell == $piece) {
								$in = true;
								break;
							}
						}
						if (!$in) {
							return false;
						}
					} elseif ($type === DataTableSearchState::not_equal) {
						if ($cell == $value) {
							return false;
						}
					} else {
						throw new Exception("Unimplemented for search type " . $type);
					}
				}
			}
		}
		return true;
	}

	/**
	 * Applies filters from $state to array and returns a copy with matched rows removed
	 * @param $array array
	 * @param $pagination_info IPaginationInfo
	 * @throws Exception
	 * @return array
	 */
	protected static function filter($array, $pagination_info)
	{
		$copy = array();
		foreach ($array as $rowid => $row) {
			if (self::accept($row, $pagination_info)) {
				$copy[$rowid] = $row;
			}
		}
		return $copy;
	}

	/**
	 * Applies filters from $state to array and returns a copy with matched rows removed (NOTE: not an in place sort)
	 * @param $array array
	 * @param $pagination_info IPaginationInfo
	 * @throws Exception
	 * @return array
	 */
	public static function sort($array, $pagination_info)
	{
		$sorting_data = $pagination_info->get_sorting_states();
		foreach ($sorting_data as $column_key => $sorting_state) {
			if (!($sorting_state instanceof DataTableSortingState)) {
				throw new Exception("sorting value should be instance of DataTableSortingState");
			}
			if ($sorting_state->get_direction() !== DataTableSortingState::sort_order_default) {
				$sorter = new ArraySorter($column_key, $sorting_state);
				usort($array, array($sorter, 'sort'));
			}
		}
		return $array;
	}

	/**
	 * @param $array array
	 * @param $pagination_info IPaginationInfo
	 * @param $num_rows int
	 * @throws Exception
	 * @return array
	 */
	protected static function paginate($array, $pagination_info, $num_rows) {
		$limit = $pagination_info->get_limit();
		$page = $pagination_info->calculate_current_page($num_rows);

		if ($limit === 0) {
			return $array;
		}
		else
		{
			$start = $limit * $page;
			return array_slice($array, $start, $limit);
		}
	}
}

/**
 * Work around lack of closures in PHP 5.2
 */
class ArraySorter {
	/** @var  string */
	protected $column_key;
	/**
	 * @var DataTableSortingState
	 */
	protected $sorting_state;

	/**
	 * @param $column_key string
	 * @param $sorting_state DataTableSortingState
	 * @throws Exception
	 */
	public function __construct($column_key, $sorting_state) {
		if (!is_string($column_key)) {
			throw new Exception("column_key must be a string");
		}
		if (!($sorting_state instanceof DataTableSortingState)) {
			throw new Exception("is_asc must be a bool");
		}
		$this->column_key = $column_key;
		$this->sorting_state = $sorting_state;
	}

	/**
	 * @param $a mixed
	 * @param $b mixed
	 * @return int
	 */
	public function sort($a, $b) {
		$type = $this->sorting_state->get_type();
		$a_value = $a[$this->column_key];
		$b_value = $b[$this->column_key];
		if ($type === DataTableSortingState::sort_type_numeric) {
			$a_value = (float)$a_value;
			$b_value = (float)$b_value;
		}
		elseif ($type === DataTableSortingState::sort_type_text) {
			$a_value = (string)$a_value;
			$b_value = (string)$b_value;
		}

		if ($a_value < $b_value) {
			$ret = -1;
		}
		elseif ($a_value == $b_value) {
			$ret = 0;
		}
		else
		{
			$ret = 1;
		}
		if ($this->sorting_state->get_direction() === DataTableSortingState::sort_order_desc) {
			$ret *= -1;
		}
		return $ret;
	}
}

/**
 * Take existing iterator and return some cell within each row as the key for the row
 */
class ColumnAsKeyIterator implements Iterator {
	/**
	 * @var Iterator
	 */
	protected $iterator;
	/**
	 * @var string
	 */
	protected $column_key;

	public function __construct($iterator, $column_key) {
		if (!($iterator instanceof Iterator)) {
			throw new Exception("iterator must be instanceof Iterator");
		}
		if (!is_string($column_key) || trim($column_key) === "") {
			throw new Exception("column_key must be non-empty string");
		}
		$this->iterator = $iterator;
		$this->column_key = $column_key;
	}

	public function current()
	{
		return $this->iterator->current();
	}

	public function next()
	{
		$this->iterator->next();
	}

	public function key()
	{
		$row = $this->iterator->current();
		return $row[$this->column_key];
	}

	public function valid()
	{
		return $this->iterator->valid();
	}

	public function rewind()
	{
		$this->iterator->rewind();
	}
}