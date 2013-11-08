<?php

/**
 * Interface meant to represent a function or closure which formats cells in a certain way
 */
interface IDataTableHeaderFormatter {
	/**
	 * Formats a header for a DataTableColumn
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_key Column key
	 * @param object $header_data Something to render as header of column
	 * @param $state DataFormState State which may contain old contents of item. May be null
	 * @return string HTML formatted column data
	 */
	public function format($form_name, $column_key, $header_data, $state);
}