<?php

/**
 * Interface meant to represent a function or closure which formats cells in a certain way
 */
interface IDataTableHeaderFormatter {
	/**
	 * Formats a header for a DataTableColumn
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_header Column key
	 * @param string $column_display_header Text to display for column header
	 * @return string HTML formatted column data
	 */
	public function format($form_name, $column_header, $column_display_header);
}