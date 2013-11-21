<?php
require_once "util.php";

/**
 * Renders a textbox or some other piece of HTML used for searching on a column
 */
interface IDataTableSearchFormatter {
	/**
	 * @param $searching_name string
	 * @param $old_search_value DataTableSearchState
	 * @return mixed
	 */
	function format($searching_name, $old_search_value);
}

class TextboxSearchFormatter implements IDataTableSearchFormatter {
	/**
	 * @var string
	 */
	protected $type;
	public function __construct($type) {
		if ($type !== DataTableSearchState::like || $type !== DataTableSearchState::rlike) {
			throw new Exception("This search formatter only supports LIKE and RLIKE searches");
		}
		$this->type = $type;
	}

	function format($searching_name, $old_search_value)
	{
		// For each column there's a hidden element that contains search state. This way we can
		// have multiple form elements adjust that hidden element

		// This piece of Javascript puts the textbox contents into JSON and stores it in the hidden state
		// The JSON is later read as DataTableSearchState
		$onchange = '$(' . json_encode("#" . jquery_escape($searching_name)) . ').attr("value", JSON.stringify({"type" : "' . $this->type . '", "params" : [$(this).val()]}));';

		if ($old_search_value) {
			if ($old_search_value->get_type() !== $this->type) {
				throw new Exception("Unexpected search type");
			}
			$params = $old_search_value->get_params();
			$value = $params[0];
		}
		else
		{
			$value = "";
		}

		$ret = '<input type="text" size="8" onchange="' . htmlspecialchars($onchange) . '" value="' . htmlspecialchars($value) . '" />';

		return $ret;
	}
}