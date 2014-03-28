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

	/**
	 * @param $table_name string
	 * @return ArrayManager
	 */
	public function table_name($table_name) {
		$this->table_name = $table_name;
		return $this;
	}

	/**
	 * @param $state DataFormState
	 * @return ArrayManager
	 */
	public function state($state) {
		$this->state = $state;
		return $this;
	}

	/**
	 * @param $settings DataTableSettings
	 * @return ArrayManager
	 */
	public function settings($settings) {
		$this->settings = $settings;
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
		if (is_null($this->table_name)) {
			$this->table_name = "";
		}
		if (!is_string($this->table_name)) {
			throw new Exception("table_name must be a string");
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

		if ($this->state && !($this->state instanceof DataFormState)) {
			throw new Exception("state must be instance of DataFormState");
		}

		if ($this->settings && !($this->settings instanceof DataTableSettings)) {
			throw new Exception("settings must be DataTableSettings");
		}
	}
	// for backwards compat
	public function make_filtered_subset() {
		list($array, $num_rows) = $this->obtain_paginated_data_and_row_count(null, null);
		return array($num_rows, $array);
	}

	public function obtain_paginated_data() {
		throw new Exception("Unimplemented for performance reasons. Use obtain_paginated_data_and_row_count() instead");
	}

	public function obtain_row_count() {
		throw new Exception("Unimplemented for performance reasons. Use obtain_paginated_data_and_row_count() instead");
	}

	public function obtain_paginated_data_and_row_count($conn_type, $rowid_key) {
		$this->validate_input();

		$array = $this->array;

		// Order is important here
		if (!$this->ignore_filtering) {
			$array = self::filter($array, $this->state, $this->settings, $this->table_name);
		}
		$num_rows = count($array);
		if ($this->settings) {
			$settings = $this->settings->make_builder()->total_rows($num_rows)->build();
		}
		else
		{
			$settings = DataTableSettingsBuilder::create()->total_rows($num_rows)->build();
		}
		$array = self::sort($array, $this->state, $settings, $this->table_name);
		if (!$this->ignore_pagination) {
			$array = self::paginate($array, $this->state, $settings, $this->table_name);
		}

		$iterator = new ArrayIterator($array);
		if ($rowid_key !== null) {
			$iterator = new ColumnAsKeyIterator($iterator, $rowid_key);
		}
		return array($iterator, $num_rows);
	}

	/**
	 * Applies filters from $state to array and returns a copy with matched rows removed
	 * @param $array array
	 * @param $state DataFormState
	 * @param $settings DataTableSettings
	 * @param $table_name string
	 * @throws Exception
	 * @return array
	 */
	protected static function filter($array, $state, $settings, $table_name) {
		if ($state) {
			if ($table_name) {
				$searching_state = $state->find_item(array(DataFormState::state_key, $table_name, DataFormState::searching_state_key));
			}
			else
			{
				$searching_state = $state->find_item(array(DataFormState::state_key, DataFormState::searching_state_key));
			}
			if (is_array($searching_state)) {
				foreach (array_keys($searching_state) as $column_key) {
					$obj = $state->get_searching_state($column_key, $table_name);
					if ($obj) {
						$params = $obj->get_params();
						$type = $obj->get_type();
						if ($type === DataTableSearchState::like ||
							$type === DataTableSearchState::rlike ||
							$type === DataTableSearchState::less_than ||
							$type === DataTableSearchState::less_or_equal ||
							$type === DataTableSearchState::greater_than ||
							$type === DataTableSearchState::greater_or_equal ||
							$type === DataTableSearchState::equal ||
							$type === DataTableSearchState::in) {

							$value = $params[0];
							// TODO: check is_numeric for numeric comparisons
							if ($value !== "") {
								$copy = array();
								if ($type === DataTableSearchState::like) {
									foreach ($array as $key => $rows) {
										if (stripos($rows[$column_key], $value) !== false) {
											$copy[$key] = $rows;
										}
									}
								}
								elseif ($type === DataTableSearchState::rlike) {
									foreach ($array as $key => $rows) {
										if (preg_match('/' . str_replace("/", "\\/", $value) . '/i', $rows[$column_key]) === 1) {
											$copy[$key] = $rows;
										}
									}
								}
								elseif ($type === DataTableSearchState::less_than) {
									foreach ($array as $key => $rows) {
										if ($rows[$column_key] < $value) {
											$copy[$key] = $rows;
										}
									}
								}
								elseif ($type === DataTableSearchState::less_or_equal) {
									foreach ($array as $key => $rows) {
										if ($rows[$column_key] <= $value) {
											$copy[$key] = $rows;
										}
									}
								}
								elseif ($type === DataTableSearchState::greater_than) {
									foreach ($array as $key => $rows) {
										if ($rows[$column_key] > $value) {
											$copy[$key] = $rows;
										}
									}
								}
								elseif ($type === DataTableSearchState::greater_or_equal) {
									foreach ($array as $key => $rows) {
										if ($rows[$column_key] >= $value) {
											$copy[$key] = $rows;
										}
									}
								}
								elseif ($type === DataTableSearchState::equal) {
									foreach ($array as $key => $rows) {
										if ($rows[$column_key] == $value) {
											$copy[$key] = $rows;
										}
									}
								}
								elseif ($type === DataTableSearchState::in) {
									$pieces = explode(",", $value);
									foreach ($array as $key => $rows) {
										foreach ($pieces as $piece) {
											if ($rows[$column_key] == $piece) {
												$copy[$key] = $rows;
												break;
											}
										}
									}
								}
								else {
									throw new Exception("Unimplemented for search type " . $type);
								}
								$array = $copy;
							}
						}
					}
				}
			}
		}
		return $array;
	}

	/**
	 * Applies filters from $state to array and returns a copy with matched rows removed
	 * @param $array array
	 * @param $state DataFormState
	 * @param $settings DataTableSettings
	 * @param $table_name string
	 * @throws Exception
	 * @return array
	 */
	protected static function sort($array, $state, $settings, $table_name) {
		if ($state) {
			if ($table_name) {
				$sorting_data = $state->find_item(array(DataFormState::state_key, $table_name, DataFormState::sorting_state_key));
			}
			else
			{
				$sorting_data = $state->find_item(array(DataFormState::state_key, DataFormState::sorting_state_key));
			}
			if (is_array($sorting_data)) {

				foreach ($sorting_data as $column_key => $value) {
					if (is_string($value)) {
						if ($value == DataFormState::sorting_state_desc ||
							$value == DataFormState::sorting_state_asc) {
							// create new ORDER clause

							$sorter = new ArraySorter($column_key);
							usort($array, array($sorter, 'sort'));
						}
						elseif ($value)
						{
							throw new Exception("Unexpected sorting value received: '$value'");
						}
					}
					else
					{
						throw new Exception("sorting value should be a string");
					}
				}
			}
		}
		return $array;
	}

	/**
	 * @param $array array
	 * @param $state DataFormState
	 * @param $settings DataTableSettings
	 * @param $table_name string
	 * @return array
	 */
	protected static function paginate($array, $state, $settings, $table_name) {
		$pagination_state = $state->get_pagination_state($table_name);

		$limit = DataTableSettings::calculate_limit($settings, $pagination_state);
		$page = DataTableSettings::calculate_current_page($settings, $pagination_state);

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
	 * @param $column_key string
	 */
	public function __construct($column_key) {
		$this->column_key = $column_key;
	}

	/**
	 * @param $a mixed
	 * @param $b mixed
	 * @return int
	 */
	public function sort($a, $b) {
		$a_value = $a[$this->column_key];
		$b_value = $b[$this->column_key];
		if ($a_value < $b_value) {
			return -1;
		}
		elseif ($a_value == $b_value) {
			return 0;
		}
		else
		{
			return 1;
		}
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