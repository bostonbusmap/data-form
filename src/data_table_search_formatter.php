<?php
require_once "util.php";

/**
 * Renders a textbox or some other piece of HTML used for searching on a column
 */
interface IDataTableSearchFormatter {
	/**
	 * @param $form_name string
	 * @param $table_name string
	 * @param $column_key string
	 * @param $state DataFormState
	 * @param $default_values DataTableSearchState[]
	 * @return mixed
	 */
	function format($form_name, $table_name, $column_key, $state, $default_values);
}

class TextboxSearchFormatter implements IDataTableSearchFormatter {
	/**
	 * @var string
	 */
	protected $type;
	public function __construct($type) {
		if ($type !== DataTableSearchState::like && $type !== DataTableSearchState::rlike) {
			throw new Exception("This search formatter only supports LIKE and RLIKE searches");
		}
		$this->type = $type;
	}

	function format($form_name, $table_name, $column_key, $state, $default_values)
	{
		if (!isset($default_values[$column_key])) {
			$default_param = "";
			$default_type = $this->type;
		}
		else
		{
			$default_params = $default_values[$column_key]->get_params();
			$default_param = $default_params[0];

			$default_type = $default_values[$column_key]->get_type();
		}

		$searching_state_key = DataFormState::get_searching_state_key($column_key, $table_name);
		$type_key = array_merge($searching_state_key, array(DataTableSearchState::type_key));
		$params_key = array_merge($searching_state_key, array(DataTableSearchState::params_key, "0"));

		$type_name = DataFormState::make_field_name($form_name, $type_key);

		// TODO: replace with DataTableHidden
		$ret = '<input type="hidden" name="' . htmlspecialchars($type_name) . '" value="' .
			htmlspecialchars($default_type) . '" />';
		$ret .= DataTableTextbox::display_textbox($form_name, $params_key, "", "", null, $default_param, null, $state);

		return $ret;
	}
}
class NumericalSearchFormatter implements IDataTableSearchFormatter {
	function format($form_name, $table_name, $column_key, $state, $default_values)
	{
		if (isset($default_values[$column_key])) {
			$default_param = "";
		}
		else
		{
			$default_params = $default_values[$column_key]->get_params();
			$default_param = $default_params[0];
		}

		// mapping of type key to type string
		$options = array();
		$options[DataTableSearchState::less_than] = new DataTableOption("<", DataTableSearchState::less_than);
		$options[DataTableSearchState::less_or_equal] = new DataTableOption("<=", DataTableSearchState::less_or_equal);
		$options[DataTableSearchState::greater_than] = new DataTableOption(">", DataTableSearchState::greater_than);
		$options[DataTableSearchState::greater_or_equal] = new DataTableOption(">=", DataTableSearchState::greater_or_equal);
		$options[DataTableSearchState::equal] = new DataTableOption("=", DataTableSearchState::equal);

		$searching_state_key = DataFormState::get_searching_state_key($column_key, $table_name);
		$type_key = array_merge($searching_state_key, array(DataTableSearchState::type_key));
		$params_key = array_merge($searching_state_key, array(DataTableSearchState::params_key, "0"));

		$ret = DataTableOptions::display_options($form_name, $type_key, "", "", null, $options, null, $state);
		$ret .= DataTableTextbox::display_textbox($form_name, $params_key, "", "", null, $default_param, null, $state);

		return $ret;
	}
}