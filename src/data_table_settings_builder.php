<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

/**
 * Builder for DataTableSettings
 */
class DataTableSettingsBuilder
{
	/** @var  int Default number of rows per page */
	protected $default_limit;
	/** @var  int|null Number of total rows in data (before pagination). If null this is assumed to be unknown */
	protected $total_rows;

	/** @var string[] Mapping of limit number to text to display for that limit number */
	protected $limit_options;

	/**
	 * @var string[] mapping of column_key to sorting direction ('asc' or 'desc')
	 */
	protected $sorting;

	/**
	 * @var DataTableSearchState[] mapping of column_key to search phrase
	 */
	protected $filtering;

	public function __construct() {
		$this->sorting = array();
		$this->filtering = array();
	}

	public static function create()
	{
		return new DataTableSettingsBuilder();
	}

	/**
	 * Default number of rows per page
	 * @param $default_limit int
	 * @return DataTableSettingsBuilder
	 */
	public function default_limit($default_limit)
	{
		$this->default_limit = $default_limit;
		return $this;
	}

	/**
	 * Number of rows in data set. Used for pagination. May be null if unknown
	 *
	 * @param $total_rows int|null
	 * @return DataTableSettingsBuilder
	 */
	public function total_rows($total_rows)
	{
		$this->total_rows = $total_rows;
		return $this;
	}

	/**
	 * Default limit values for select options.
	 *
	 * @param $limit_options array Must be int => string, where int is the value and string is text to display
	 * @return DataTableSettingsBuilder
	 */
	public function limit_options($limit_options)
	{
		$this->limit_options = $limit_options;
		return $this;
	}

	/**
	 * Sort $column_key by $direction, which must be 'asc' or 'desc'
	 *
	 * @param $column_key string
	 * @param $direction string
	 * @return DataTableSettingsBuilder
	 */
	public function sort_by($column_key, $direction)
	{
		$this->sorting[$column_key] = $direction;
		return $this;
	}

	/**
	 * Filter $column_key by $text. Whether $text is a regex or simple string search is up to application
	 *
	 * @param $column_key string
	 * @param $search_state DataTableSearchState
	 * @return $this
	 * @throws Exception
	 */
	public function filter_by($column_key, $search_state)
	{
		$this->filtering[$column_key] = $search_state;
		return $this;
	}

	/**
	 * @return int Default number of rows per page
	 */
	public function get_default_limit()
	{
		return $this->default_limit;
	}

	/**
	 * @return int|null Number of rows in data (without pagination, but after filtering). May be null if value is unknown
	 */
	public function get_total_rows()
	{
		return $this->total_rows;
	}

	/**
	 * @return string[] Mapping of limit number to text to display for that limit number
	 */
	public function get_limit_options()
	{
		return $this->limit_options;
	}

	/**
	 * @return string[] mapping of column_key to sorting direction ('asc' or 'desc')
	 */
	public function get_sorting()
	{
		return $this->sorting;
	}

	/**
	 * @return DataTableSearchState[] mapping of column_key to search phrase
	 */
	public function get_filtering()
	{
		return $this->filtering;
	}

	/**
	 * Validate input and create DataTableSettings
	 * 
	 * @return DataTableSettings
	 * @throws Exception
	 */
	public function build()
	{
		if (is_null($this->default_limit)) {
			$this->default_limit = DataTableSettings::default_limit;
		}
		if (!is_int($this->default_limit)) {
			throw new Exception("default_limit must be an integer");
		}

		if (!is_null($this->total_rows) && !is_int($this->total_rows)) {
			throw new Exception("total_rows must be an integer");
		}

		if (is_null($this->limit_options)) {
			$this->limit_options = array(
				0 => "ALL",
				10 => "10",
				25 => "25",
				50 => "50",
				100 => "100",
				500 => "500",
				1000 => "1000"
			);
		}
		if (!is_array($this->limit_options)) {
			throw new Exception("limit_options must be an array");
		}
		foreach ($this->limit_options as $k => $v) {
			if (!is_int($k) || !is_string($v)) {
				throw new Exception("limit_options must be made of an array of ints => strings," .
					" where the int is the value and the string is the text to display");
			}
		}

		// user shouldn't be able to change sorting from an array, but just in case
		if (is_null($this->sorting)) {
			$this->sorting = array();
		}
		if (!is_array($this->sorting)) {
			throw new Exception("sorting must be an array");
		}
		foreach ($this->sorting as $k => $v) {
			if (!is_string($k) || trim($k) === "") {
				throw new Exception("Each column_key in sorting must be a string and must exist");
			}
			if ($v != DataFormState::sorting_state_asc && $v != DataFormState::sorting_state_desc) {
				throw new Exception("Each value must be either 'asc' or 'desc'");
			}
		}

		// user shouldn't be able to change filtering from an array, but just in case
		if (!$this->filtering) {
			$this->filtering = array();
		}
		if (!is_array($this->filtering)) {
			throw new Exception("filtering must be an array");
		}
		foreach ($this->filtering as $k => $v) {
			if (!is_string($k) || trim($k) === "") {
				throw new Exception("Each column_key in filtering must be a string and must exist");
			}
			if (!($v instanceof DataTableSearchState)) {
				throw new Exception("filtering values must be instances of DataTableSearchState");
			}
		}

		return new DataTableSettings($this);
	}
}