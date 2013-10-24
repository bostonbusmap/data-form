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
	 * @param int $rowid Row id number
	 * @return string HTML for a checkbox
	 */
	public function format($form_name, $column_header, $column_data, $rowid)
	{
		// TODO: sanitize for HTML
		return "<input type='checkbox' name='" . $form_name . "[$column_header][]' value='$rowid' />";
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
		$ret = "<input type='checkbox' onclick='$(this).parents(\"form\").find(\"td.column_$column_header :checkbox\").prop(\"checked\", this.checked)' />";
		return $ret;
	}
}