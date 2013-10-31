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
		if ($state && is_array($state->find_item(array($column_header)))) {
			$checked_items = $state->find_item(array($column_header));
			$checked = (in_array($rowid, $checked_items) ? "checked" : "");
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
		$input_name = $form_name . "[$column_header][$rowid]";
		return '<input type="checkbox" name="' . htmlspecialchars($input_name) . '" value="' . htmlspecialchars($column_data) . '" ' . $checked . ' />';
	}
}

class DataTableCheckboxHeaderFormatter implements IDataTableHeaderFormatter {

	/**
	 * Displays a checkbox which selects all other checkboxes
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_header Column key
	 * @param string $column_display_header Unused
	 * @return string HTML formatted column data
	 */
	public function format($form_name, $column_header, $column_display_header)
	{
		// this selects or clears all other checkboxes in column
		$onclick = '$(this).parents("form").find(' . json_encode('td.column_' . $column_header . ' :checkbox') . ').prop("checked", this.checked)';
		$ret = '<input type="checkbox" onclick="' . htmlspecialchars($onclick) . '" />';
		return $ret;
	}
}