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
		return self::format_checkbox($form_name, $column_header, $column_data, $rowid, $state);
	}

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
	public static function format_checkbox($form_name, $column_header, $column_data, $rowid, $state) {
		if ($state && $state->has_item(array($column_header, $rowid))) {
			$checked_item = $state->find_item(array($column_header, $rowid));

			$checked = ((string)$column_data === (string)$checked_item ? "checked" : "");
		}
		else
		{
			if ($column_data instanceof Selected) {
				$checked = ($column_data->is_selected() ? "checked" : "");
			}
			else
			{
				$checked = "";
			}
		}

		$name_array = array($column_header, $rowid);
		$input_name = DataFormState::make_field_name($form_name, array($column_header, $rowid));
		$ret = '<input type="checkbox" name="' . htmlspecialchars($input_name) . '" value="' . htmlspecialchars($column_data) . '" ' . $checked . ' />';

		// Create hidden field to allow detection of unchecked
		if ($state) {
			$hidden_key = array_merge(DataFormState::get_hidden_state_key(), $name_array);
			if ($state->has_item($hidden_key)) {
				// Item is defined in history
				// We need this blank hidden field to prevent history overwriting a missing value
				$blank_name = array_merge(DataFormState::get_blanks_key(), $name_array);
				$ret .= DataTableHidden::display_hidden($form_name, $state, $blank_name, "");
			}
		}

		return $ret;
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
		$onclick = '$(this).parents("form").find(' . json_encode('td.column_' . $column_key . ' :checkbox') . ').prop("checked", this.checked)';
		$ret = '<input type="checkbox" onclick="' . htmlspecialchars($onclick) . '" />';
		return $ret;
	}
}