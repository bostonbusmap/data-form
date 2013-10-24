<?php

require_once "data_table_cell_formatter.php";
require_once "data_table_header_formatter.php";

class DefaultCellFormatter implements IDataTableCellFormatter {

	/**
	 * Formats a cell for a DataTableColumn
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_header Column key
	 * @param object $column_data Data to be shown for the cell
	 * @param int $rowid ID for the cell's row
	 * @return string HTML formatted column data
	 */
	public function format($form_name, $column_header, $column_data, $rowid)
	{
		return $column_data;
	}
}

class DefaultHeaderFormatter implements IDataTableHeaderFormatter {

	/**
	 * Formats a header for a DataTableColumn
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_header Column key
	 * @param string $column_display_header Text to display
	 * @return string HTML formatted column data
	 */
	public function format($form_name, $column_header, $column_display_header)
	{
		return "<strong>" . $column_display_header . "</strong>";
	}
}

/**
 * An object which displays column data in an HTML table. Use with display_sql_table_form in data_table.php
 *
 * This uses callbacks to transform the cell data into something with HTML which is then printed in the cell
 *
 * This can also be used to provide checkboxes, select widgets or whatever else.
 *
 * See default_display_header and default_display_data for default implementations of callbacks
 */
class DataTableColumn {
	/**
	 * @var IDataTableHeaderFormatter Callback to format header data for display
	 */
	protected $header_formatter;
	/**
	 * @var IDataTableCellFormatter Callback to format cell data for display
	 */
	protected $cell_formatter;
	/**
	 * @return bool|string Is column meant to be sortable by clicking on the column header?
	 * Either false or this contains one of "numeric", "alphanumeric", etc which are used as CSS classes
	 */
	protected $sortable;
	/**
	 * @var bool Should column be searched by user?
	 */
	protected $searchable;
	/**
	 * @var string Column header meant to be displayed
	 */
	protected $display_header_name;

	/**
	 * @param string $display_header_name The name for the column meant to be printed
	 * @param IDataTableCellFormatter $cell_formatter A callback to format column cell data. See default_display_data for example
	 * @param IDataTableHeaderFormatter $header_formatter A callback to format column header. See default_display_header for example
	 * @param bool|string $sortable Should column be sortable? Either false or a CSS class ('numeric', 'alphanumeric', etc)
	 * @param bool $searchable Should column be searchable?
	 */
	public function __construct($display_header_name = "", $cell_formatter = null, $header_formatter = null, $sortable = false, $searchable = false) {
		if ($header_formatter) {
			$this->header_formatter = $header_formatter;
		}
		else
		{
			$this->header_formatter = new DefaultHeaderFormatter();
		}

		if ($cell_formatter) {
			$this->cell_formatter = $cell_formatter;
		}
		else
		{
			$this->cell_formatter = new DefaultCellFormatter();
		}
		$this->sortable = $sortable;
		$this->searchable = $searchable;
		$this->display_header_name = $display_header_name;
	}

	/**
	 * Returns HTML formatted column header
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_header Column key
	 * @return string
	 */
	public function get_display_header($form_name, $column_header) {
		return $this->header_formatter->format($form_name, $column_header, $this->display_header_name);
	}

	/**
	 * Returns HTML formatted version of $column_data to print out
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_header Column key
	 * @param object $column_data Data to display
	 * @param int $rowid ID of cell's row
	 * @return string
	 */
	public function get_display_data($form_name, $column_header, $column_data, $rowid) {
		return $this->cell_formatter->format($form_name, $column_header, $column_data, $rowid);
	}

	/**
	 * @return bool Is column meant to be searchable using a regex filter?
	 */
	public function get_searchable() {
		return $this->searchable;
	}

	/**
	 * @return bool|string Is column meant to be sortable by clicking on the column header?
	 * Either false or this contains one of "numeric", "alphanumeric", etc which are used as CSS classes
	 */
	public function get_sortable() {
		return $this->sortable;
	}

}
