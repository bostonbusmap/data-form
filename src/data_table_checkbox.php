<?php

require_once("data_table.php");
require_once("data_table_column.php");

class DataTableCheckboxCellFormatter implements IDataTableCellFormatter {

	/**
	 * Implementation to display a checkbox
	 *
	 * @param string $form_name The name of the form
	 * @param string $column_header Name of column
	 * @param object $column_data Unused
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
	 * @param object $column_data Unused
	 * @param string $rowid Row id
	 * @param DataFormState $state
	 * @return string HTML for a checkbox
	 */
	public static function format_checkbox($form_name, $column_header, $column_data, $rowid, $state) {
		if ($state && is_array($state->find_item(array($column_header)))) {
			$checked_item = $state->find_item(array($column_header, $rowid));
			$checked = ($column_data == $checked_item ? "checked" : "");
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

		$input_name = DataFormState::make_field_name($form_name, array($column_header, $rowid));
		$hidden_name = DataFormState::make_field_name($form_name, array(DataFormState::state_key, DataFormState::blanks_key, $column_header, $rowid));
		$ret = '<input type="checkbox" name="' . htmlspecialchars($input_name) . '" value="' . htmlspecialchars($column_data) . '" ' . $checked . ' />';
		$ret .= '<input type="hidden" name="' . htmlspecialchars($hidden_name) . '" value="" />';

		return $ret;
	}
}

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