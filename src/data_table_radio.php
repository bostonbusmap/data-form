<?php
class DataTableRadioFormatter implements IDataTableCellFormatter {

	/**
	 * Writes out radio buttons corresponding to the row id
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_header Column key
	 * @param object $column_data Unused
	 * @param int $rowid ID for the cell's row
	 * @return string HTML formatted column data
	 */
	public function format($form_name, $column_header, $column_data, $rowid)
	{
		// TODO: sanitize for HTML
		return "<input type='radio' name='" . $form_name . "[$column_header][]' value='$rowid' />";
	}
}