<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */
require_once "util.php";

/**
 * Renders a textbox or some other piece of HTML used for searching on a column
 */
interface IDataTableSearchFormatter {
	/**
	 * Render HTML for the search control
	 *
	 * TODO: figure out a way to simplify this interface, remove some parameters
	 *
	 * @param $form_name string
	 * @param $form_action string URL to refresh to
	 * @param $form_method string Either post or get
	 * @param $table_name string Name of table if table has a name
	 * @param $column_key string Which column search formatter applies to
	 * @param $state DataFormState State of form
	 * @param $default_value DataTableSearchState whatever the user filled in when the refresh happened
	 * @param $label string HTML to display for the label for this element. May be empty string for no label
	 * @return string
	 */
	function format($form_name, $form_action, $form_method, $table_name, $column_key, $state, $default_value, $label);
}

/**
 * Renders a textbox for string searching
 */
class TextboxSearchFormatter implements IDataTableSearchFormatter {
	/**
	 * @var string Type of search
	 */
	protected $type;
	public function __construct($type) {
		if ($type !== DataTableSearchState::like &&
			$type !== DataTableSearchState::rlike &&
			$type !== DataTableSearchState::equal &&
			$type !== DataTableSearchState::in) {
			throw new Exception("This search formatter only supports LIKE, RLIKE, EQUAL and IN searches");
		}
		$this->type = $type;
	}

	function format($form_name, $form_action, $form_method, $table_name, $column_key, $state, $default_value, $label)
	{
		if (is_null($default_value)) {
			$default_param = "";
			$default_type = $this->type;
		}
		else
		{
			$default_params = $default_value->get_params();
			$default_param = $default_params[0];

			$default_type = $default_value->get_type();
		}

		$searching_state_key = DataFormState::get_searching_state_key($table_name);
		$type_key = array_merge($searching_state_key, array($column_key, DataTableSearchState::type_key));
		$params_key = array_merge($searching_state_key, array($column_key, DataTableSearchState::params_key, "0"));

		$ret = DataTableHidden::display_hidden($form_name, $state, $type_key, $default_type);
		$ret .= DataTableTextbox::display_textbox($form_name, $params_key, $form_action, $form_method, new DataTableBehaviorRefresh(), $default_param, $label, $state);

		return $ret;
	}
}

/**
 * Renders a textbox for numerical filtering
 */
class NumericalSearchFormatter implements IDataTableSearchFormatter {
	function format($form_name, $form_action, $form_method, $table_name, $column_key, $state, $default_value, $label)
	{
		if (is_null($default_value)) {
			$default_param = "";
		}
		else
		{
			$default_params = $default_value->get_params();
			$default_param = $default_params[0];
		}

		// mapping of type key to type string
		$options = array();
		$options[DataTableSearchState::less_than] = new DataTableOption("<", DataTableSearchState::less_than);
		$options[DataTableSearchState::less_or_equal] = new DataTableOption("<=", DataTableSearchState::less_or_equal);
		$options[DataTableSearchState::greater_than] = new DataTableOption(">", DataTableSearchState::greater_than);
		$options[DataTableSearchState::greater_or_equal] = new DataTableOption(">=", DataTableSearchState::greater_or_equal);
		$options[DataTableSearchState::equal] = new DataTableOption("=", DataTableSearchState::equal);

		$searching_state_key = DataFormState::get_searching_state_key($table_name);
		$type_key = array_merge($searching_state_key, array($column_key, DataTableSearchState::type_key));
		$params_key = array_merge($searching_state_key, array($column_key, DataTableSearchState::params_key, "0"));

		// surrounding this with a div to allow CSS to set column width
		$ret = "<div>";
		$ret .= DataTableOptions::display_options($form_name, $type_key, $form_action, $form_method,  new DataTableBehaviorRefresh(), $options, $label, $state);
		$ret .= DataTableTextbox::display_textbox($form_name, $params_key, $form_action, $form_method,  new DataTableBehaviorRefresh(), $default_param, $label, $state);
		$ret .= "</div>";

		return $ret;
	}
}