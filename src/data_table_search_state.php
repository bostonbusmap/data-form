<?php
/**
 * The search parameters for a particular column. This can be simple in the case of a textbox or more complicated
 * for numerical comparisons for example
 *
 * This either comes from DataFormState or the user may construct it to specify default values
 */
class DataTableSearchState {
	/**
	 * Non-regex string matching. Case sensitivity depends on collation of database, but usually this is case insensitive
	 */
	const like = "LIKE";

	/**
	 * Regex string matching. Case sensitivity depends on collation of database, but usually this is case insensitive
	 */
	const rlike = "RLIKE";

	/** Filter to display numbers less than the number specified */
	const less_than = "less";
	/** Filter to display numbers greater than the number specified */
	const greater_than = "greater";
	/** Filter to display numbers less or requal to the number specified */
	const less_or_equal = "less_or_equal";
	/** Filter to display numbers greater or equal to number specified */
	const greater_or_equal = "greater_or_equal";
	/** Filter to display numbers equal to number specified */
	const equal = "equal";

	/**
	 * Field specifying type of search
	 */
	const type_key = "_type";
	/**
	 * Field specifying array of parameters for search
	 */
	const params_key = "_params";

	/**
	 * @var string Type of search
	 */
	protected $type;
	/**
	 * @var string[] Parameters for search
	 */
	protected $params;

	public function __construct($type, $params) {
		if (!is_array($params)) {
			throw new Exception("params must be an array");
		}
		foreach ($params as $param) {
			if (!is_string($param)) {
				throw new Exception("Each parameter must be a string");
			}
		}

		if ($type === self::like ||
			$type === self::rlike ||
			$type === self::equal ||
			$type === self::greater_than ||
			$type === self::greater_or_equal ||
			$type === self::less_than ||
			$type === self::less_or_equal) {
			if (count($params) !== 1) {
				throw new Exception("$type must have one parameter");
			}
		}
		else
		{
			throw new Exception("Unexpected search type " . $type . " found");
		}

		$this->type = $type;
		$this->params = $params;
	}

	/**
	 * @return string Type of search
	 */
	function get_type() {
		return $this->type;
	}

	/**
	 * @return string[] Parameters for search
	 */
	function get_params() {
		return $this->params;
	}

}