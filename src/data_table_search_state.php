<?php
class DataTableSearchState {
	/**
	 * Non-regex string matching. Case sensitivity depends on collation of database, but usually this is case insensitive
	 */
	const like = "LIKE";

	/**
	 * Regex string matching. Case sensitivity depends on collation of database, but usually this is case insensitive
	 */
	const rlike = "LIKE";

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

		if ($type === self::like) {
			if (count($params) !== 1) {
				throw new Exception("LIKE must have one parameter");
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

	/**
	 * @return string
	 */
	function to_json() {
		$x = array("type" => $this->type, "params" => $this->params);
		return json_encode($x);
	}

	/**
	 * Factory method which creates this object from JSON. If $json is empty, null will be returned.
	 * If something is in $json but it's not being parsed, an exception will be thrown
	 *
	 * @param $json string
	 * @returns DataTableSearchState
	 * @throws Exception
	 */
	public static function from_json($json) {
		if (!is_null($json)) {
			if (!is_string($json)) {
				throw new Exception("Expected search_json to be a string");
			}
			if (trim($json) !== "") {
				$search = json_decode($json);
				if (!$search) {
					throw new Exception("search JSON wasn't parsed correctly");
				}
				$type = $search->type;
				$params = $search->params;
				return new DataTableSearchState($type, $params);
			}
		}
		return null;
	}
}