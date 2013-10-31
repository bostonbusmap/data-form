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
		if ($state && !is_null($state->find_item(array($column_header)))) {
			$selected_item = $state->find_item(array($column_header));
			$checked = ($selected_item === $column_data ? "checked" : "");
		}
		else
		{
			$selected_item = null;
			if ($column_data instanceof Selected) {
				$checked = ($column_data->is_selected() ? "checked" : "");
			}
			else
			{
				$checked = "";
			}
		}
		return '<input type="radio" name="' . htmlspecialchars($form_name . "[$column_header]") . '" value="' . htmlspecialchars($column_data) . '" ' . $checked . ' />';
	}
}