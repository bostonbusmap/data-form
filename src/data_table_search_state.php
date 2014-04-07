<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once "pagination_info.php";

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
	const less_than = "<";
	/** Filter to display numbers greater than the number specified */
	const greater_than = ">";
	/** Filter to display numbers less or requal to the number specified */
	const less_or_equal = "<=";
	/** Filter to display numbers greater or equal to number specified */
	const greater_or_equal = ">=";
	/** Filter to display numbers equal to number specified */
	const equal = "=";
	const in = "IN";

	/**
	 * Field specifying type of search
	 */
	const type_key = "_t";
	/**
	 * Field specifying array of parameters for search
	 */
	const params_key = "_p";

	/**
	 * @var string Type of search state (see constants)
	 */
	protected $type;
	/**
	 * @var string[] Parameters for search
	 */
	protected $params;

	/**
	 * @param $type string
	 * @param $params string[]
	 * @throws Exception
	 */
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
			$type === self::less_or_equal ||
			$type === self::in) {
			if (count($params) !== 1) {
				throw new Exception("$type must have one parameter");
			}
			// in the future we may want to support x between y and z type of filtering
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