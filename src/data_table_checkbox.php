<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once("data_table.php");
require_once("data_table_column.php");

/**
 * A checkbox widget
 */
class DataTableCheckbox implements IDataTableWidget {
	/**
	 * @var string Field name
	 */
	protected $name;
	/**
	 * @var string Checkbox value
	 */
	protected $value;
	/**
	 * @var bool Checked by default?
	 */
	protected $checked_by_default;
	/**
	 * @var IDataTableBehavior What happens when checkbox is clicked
	 */
	protected $behavior;
	/**
	 * @var string Either 'top' or 'bottom'
	 */
	protected $placement;
	/**
	 * @var string URL for behavior's action
	 */
	protected $form_action;
	/**
	 * @var string HTML label
	 */
	protected $label;

	/**
	 * @param $builder DataTableCheckboxBuilder
	 * @throws Exception
	 */
	public function __construct($builder) {
		if (!($builder instanceof DataTableCheckboxBuilder)) {
			throw new Exception("builder must be of type DataTableCheckboxBuilder");
		}

		$this->name = $builder->get_name();
		$this->value = $builder->get_value();
		$this->checked_by_default = $builder->get_checked_by_default();
		$this->behavior = $builder->get_behavior();
		$this->placement = $builder->get_placement();
		$this->form_action = $builder->get_form_action();
		$this->label = $builder->get_label();
	}

	public function display($form_name, $form_method, $state)
	{
		return self::format_checkbox($form_name, $form_method, $this->form_action, array($this->name), $this->value, $this->checked_by_default, $this->behavior, $state, $this->label);
	}

	public function get_placement()
	{
		return $this->placement;
	}

	/**
	 * Implementation to display a checkbox
	 *
	 * @param string $form_name The name of the form
	 * @param string $form_method POST or GET
	 * @param string $form_action If behavior is defined, use this for the URL when checkbox is clicked
	 * @param string[] $name_array
	 * @param object $column_data Value for checkbox
	 * @param bool $checked_by_default Should checkbox be checked by default?
	 * @param IDataTableBehavior $behavior What happens when checkbox is clicked
	 * @param DataFormState $state State of form
	 * @param string $label HTML label
	 * @return string HTML for a checkbox
	 */
	public static function format_checkbox($form_name, $form_method, $form_action, $name_array, $column_data, $checked_by_default, $behavior, $state, $label) {
		if ($state && $state->has_item($name_array)) {
			$checked_item = $state->find_item($name_array);

			$checked = ((string)$column_data === (string)$checked_item ? "checked" : "");
		}
		else
		{
			if ($column_data instanceof Selected) {
				$checked = ($column_data->is_selected() ? "checked" : "");
			}
			elseif ($checked_by_default) {
				$checked = "checked";
			}
			else
			{
				$checked = "";
			}
		}

		$input_name = DataFormState::make_field_name($form_name, $name_array);
		if ($behavior) {
			$onclick = $behavior->action($form_name, $form_action, $form_method);
		}
		else {
			$onclick = "";
		}

		$ret = "";
		if ($label !== "") {
			$ret .= '<label for="' . htmlspecialchars($input_name) . '">' . $label . '</label>';
		}

		$ret .= '<input type="checkbox" id="' . htmlspecialchars($input_name) . '" name="' . htmlspecialchars($input_name) . '" value="' . htmlspecialchars($column_data) . '" onclick="' . htmlspecialchars($onclick) . '" ' . $checked . ' />';

		// Create hidden field to allow detection of unchecked
		if ($checked) {
			// $blank_name is meant to convey that the name existed in form even if the checkbox is unchecked
			// It says to not use any hidden state, just the checkbox value if present or null if not present.

			// Note that all of history is already incorporated into $state in DataFormState's constructor
			// so we don't need to consider it here.

			// If not checked we don't want to display this because it's unnecessary and will add many hidden fields
			// to a form. We only want to cover the case where a checked item is unchecked.
			$blank_name = array_merge(DataFormState::get_blanks_key(), $name_array);
			$ret .= DataTableHidden::display_hidden($form_name, $state, $blank_name, "");
		}

		return $ret;
	}
}

/**
 * Renders a checkbox whose value is the cell data
 *
 * Note that this also creates a hidden field in DataFormState::blanks_key to figure out when
 * checkbox is unchecked.
 */
class DataTableCheckboxCellFormatter implements IDataTableCellFormatter {

	/**
	 * Implementation to display a checkbox
	 *
	 * @param string $form_name The name of the form
	 * @param string $column_header Name of column
	 * @param object $column_data Value for checkbox
	 * @param string $rowid Row id
	 * @param DataFormState $state
	 * @return string HTML for a checkbox
	 */
	public function format($form_name, $column_header, $column_data, $rowid, $state)
	{
		if ($state) {
			$name_array = array($column_header, $rowid);
		}
		else
		{
			$name_array = array();
		}

		return DataTableCheckbox::format_checkbox($form_name, "", "POST", $name_array, $column_data, false, null, $state, "");
	}

}

/**
 * Displays a checkbox which selects all other checkboxes in column
 */
class DataTableCheckboxHeaderFormatter implements IDataTableHeaderFormatter {

	/**
	 * Displays a checkbox which selects all other checkboxes
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_key Column key
	 * @param string $header_data Unused
	 * @param DataFormState $state
	 * @return string HTML formatted column data
	 */
	public function format($form_name, $column_key, $header_data, $state)
	{
		// this selects or clears all other checkboxes in column
		$onclick = '$(this).parents("form").find(' . json_encode('td.column-' . $column_key . ' :checkbox') . ').prop("checked", this.checked)';
		$ret = '<input type="checkbox" onclick="' . htmlspecialchars($onclick) . '" />';
		return $ret;
	}
}