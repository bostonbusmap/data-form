<?php
/**
 * The search parameters for a particular column. This can be simple in the case of a textbox or more complicated
 * for numerical comparisons for example
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

	const less_than = "less";
	const greater_than = "greater";
	const less_or_equal = "less_or_equal";
	const greater_or_equal = "greater_or_equal";
	const equal = "equal";

	const type_key = "_type";
	const params_key = "_params";

	/**
	 * @var string
	 */
	protected $type;
	/**
	 * @var string[]
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

	function get_type() {
		return $this->type;
	}
	function get_params() {
		return $this->params;
	}

}