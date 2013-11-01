<?php
class DataTableSettingsBuilder
{
	/** @var  int */
	protected $default_limit;
	/** @var  int */
	protected $total_rows;

	/** @var string[] Mapping of limit number to text to display for that limit number */
	protected $limit_options;

	/**
	 * @var string[] mapping of column_key to sorting direction ('asc' or 'desc')
	 */
	protected $sorting;

	/**
	 * @var string[] mapping of column_key to search phrase
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

	public function default_limit($default_limit)
	{
		$this->default_limit = $default_limit;
		return $this;
	}

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
	 * @param $column_key
	 * @param $text
	 * @return $this
	 */
	public function filter_by($column_key, $text)
	{
		$this->filtering[$column_key] = $text;
		return $this;
	}

	public function get_default_limit()
	{
		return $this->default_limit;
	}

	public function get_total_rows()
	{
		return $this->total_rows;
	}

	public function get_limit_options()
	{
		return $this->limit_options;
	}

	public function get_sorting()
	{
		return $this->sorting;
	}

	public function get_filtering()
	{
		return $this->filtering;
	}

	public function build()
	{
		if (!$this->default_limit) {
			$this->default_limit = 25;
		}
		if (!is_int($this->default_limit)) {
			throw new Exception("default_limit must be an integer");
		}

		if (!$this->total_rows && !is_int($this->total_rows)) {
			throw new Exception("total_rows must be an integer");
		}

		if (!$this->limit_options) {
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
		if (!$this->sorting) {
			$this->sorting = array();
		}
		if (!is_array($this->sorting)) {
			throw new Exception("sorting must be an array");
		}
		foreach ($this->sorting as $k => $v) {
			if (!$k || !is_string($k)) {
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
			if (!$k || !is_string($k)) {
				throw new Exception("Each column_key in filtering must be a string and must exist");
			}
			if (!is_string($v)) {
				throw new Exception("filtering values must be strings");
			}
		}

		return new DataTableSettings($this);
	}
}