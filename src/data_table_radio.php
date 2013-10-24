<?php
class DataTableRadioFormatter implements IDataTableCellFormatter {

	/**
	 * Writes out radio buttons corresponding to the row id
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_header Column key
	 * @param object $column_data Unused
	 * @param string $rowid ID for the cell's row
	 * @param DataFormState $state
	 * @return string HTML formatted column data
	 */
	public function format($form_name, $column_header, $column_data, $rowid, $state)
	{
		// TODO: sanitize for HTML
		$selected_items = $state->find_item(array($column_header));
		if (is_array($selected_items) && in_array($rowid, $selected_items)) {
			$checked = "checked";
		}
		else
		{
			$checked = "";
		}
		return "<input type='radio' name='" . $form_name . "[$column_header][]' value='$rowid' $checked />";
	}
}