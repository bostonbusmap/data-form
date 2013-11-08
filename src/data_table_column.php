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
	 * @param DataFormState $state State of form, in case we want to refresh form but keep existing values
	 * @return string HTML formatted column data
	 */
	public function format($form_name, $column_header, $column_data, $rowid, $state)
	{
		return $column_data;
	}
}

class DefaultHeaderFormatter implements IDataTableHeaderFormatter {

	/**
	 * Formats a header for a DataTableColumn
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_key Column key
	 * @param string $header_data Text to display
	 * @param DataFormState $state
	 * @return string HTML formatted column data
	 */
	public function format($form_name, $column_key, $header_data, $state)
	{
		return "<strong>" . $header_data . "</strong>";
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
	 * @var object Column header data to be displayed
	 */
	protected $display_header_name;

	/**
	 * @var string Key which matches column to data
	 */
	protected $column_key;

	/**
	 * @var string CSS for this column
	 */
	protected $css;

	/**
	 * @param $builder DataTableColumnBuilder
	 * @throws Exception
	 */
	public function __construct($builder) {
		if (!($builder instanceof DataTableColumnBuilder)) {
			throw new Exception("builder expected to be instance of DataTableColumnBuilder");
		}
		$this->header_formatter = $builder->get_header_formatter();
		$this->cell_formatter = $builder->get_cell_formatter();
		$this->sortable = $builder->get_sortable();
		$this->searchable = $builder->get_searchable();
		$this->display_header_name = $builder->get_display_header_name();
		$this->column_key = $builder->get_column_key();
		$this->css = $builder->get_css();
	}

	/**
	 * Returns HTML formatted column header
	 *
	 * @param string $form_name Name of HTML form
	 * @param $column_key string Column key
	 * @param $state DataFormState State of form
	 * @return string
	 */
	public function get_display_header($form_name, $column_key, $state) {
		return $this->header_formatter->format($form_name, $column_key, $this->display_header_name, $state);
	}

	/**
	 * Returns HTML formatted version of $column_data to print out
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_header Column key
	 * @param object $column_data Data to display
	 * @param int $rowid ID of cell's row
	 * @param DataFormState $state State of form
	 * @return string
	 */
	public function get_display_data($form_name, $column_header, $column_data, $rowid, $state) {
		return $this->cell_formatter->format($form_name, $column_header, $column_data, $rowid, $state);
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

	/**
	 * @return string
	 */
	public function get_column_key() {
		return $this->column_key;
	}

	/**
	 * @return string
	 */
	public function get_css()
	{
		return $this->css;
	}
}
