<?php
require_once "util.php";

/**
 * Renders a textbox or some other piece of HTML used for searching on a column
 */
interface IDataTableSearchFormatter {
	/**
	 * @param $searching_name string
	 * @param $old_search_value string
	 * @return mixed
	 */
	function format($searching_name, $old_search_value);
}

class DefaultSearchFormatter implements IDataTableSearchFormatter {

	function format($searching_name, $old_search_value)
	{
		$onchange = '$(' . json_encode("#" . jquery_escape($searching_name)) . ').attr("value", JSON.stringify({"command" : "LIKE", "values" : [$(this).val()]}));';

		$old_search_value_obj = json_decode($old_search_value);
		if ($old_search_value_obj) {
			$value = $old_search_value_obj->values[0];
		}
		else
		{
			$value = "";
		}

		$ret = '<input type="text" size="8" onchange="' . htmlspecialchars($onchange) . '" value="' . htmlspecialchars($value) . '" />';

		return $ret;
	}
}