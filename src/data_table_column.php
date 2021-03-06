<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once "data_table_cell_formatter.php";
require_once "data_table_header_formatter.php";

/**
 * Default cell formatter. Returns unaltered cell data
 */
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
		return htmlspecialchars((string)$column_data);
	}
}

/**
 * Default column header formatter.
 */
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
		return htmlspecialchars((string)$header_data);
	}
}

/**
 * An object which stores column data in an HTML table. Each one of these corresponds to the column
 * rendered in the table.
 *
 * The column_key is matched with the same key in DataTable::rows to figure out what data to display.
 *
 * This uses $cell_formatter to transform the cell data into something with HTML (numbers, checkboxes, etc)
 * which is then printed in the cell.
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
	 * @var IDataTableSearchFormatter
	 */
	protected $search_formatter;
	/**
	 * @var string Column header data to be displayed
	 */
	protected $display_header_name;
	/**
	 * @var string Column footer data to be displayed
	 */
	protected $display_footer_name;

	/**
	 * @var string Key which matches column to data
	 */
	protected $column_key;

	/**
	 * @var string CSS class to apply to each cell in the column, if any
	 */
	protected $css_class;

	/**
	 * Use DataTableColumnBuilder::build()
	 *
	 * @param $builder DataTableColumnBuilder
	 * @throws Exception
	 */
	public function __construct($builder) {
		if (!($builder instanceof DataTableColumnBuilder)) {
			throw new Exception("builder expected to be instance of DataTableColumnBuilder");
		}
		$this->header_formatter = $builder->get_header_formatter();
		$this->footer_formatter = $builder->get_footer_formatter();
		$this->cell_formatter = $builder->get_cell_formatter();
		$this->sortable = $builder->get_sortable();
		$this->searchable = $builder->get_searchable();
		$this->search_formatter = $builder->get_search_formatter();
		$this->display_header_name = $builder->get_display_header_name();
		$this->display_footer_name = $builder->get_display_footer_name();
		$this->column_key = $builder->get_column_key();
		$this->css_class = $builder->get_css_class();
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
	 * Returns HTML formatted column footer
	 *
	 * @param string $form_name Name of HTML form
	 * @param $column_key string Column key
	 * @param $state DataFormState State of form
	 * @return string
	 */
	public function get_display_footer($form_name, $column_key, $state) {
		return $this->footer_formatter->format($form_name, $column_key, $this->display_footer_name, $state);
	}

	/**
	 * Returns HTML formatted version of $column_data to print out
	 *
	 * @param string $form_name Name of HTML form
	 * @param string $column_header Column key
	 * @param object $column_data Data to display
	 * @param string $rowid ID of cell's row
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
	 * Returns formatter used for search boxes in column header
	 *
	 * @return IDataTableSearchFormatter
	 */
	public function get_search_formatter() {
		return $this->search_formatter;
	}

	/**
	 * @return bool|string Is column meant to be sortable by clicking on the column header?
	 * Either false or this contains one of "numeric", "alphanumeric", etc which are used as CSS classes
	 */
	public function get_sortable() {
		return $this->sortable;
	}

	/**
	 * The column key used to look up data for each cell in DataTable->rows
	 *
	 * @return string
	 */
	public function get_column_key() {
		return $this->column_key;
	}

	/**
	 * A css class to apply to each row in the column, if any
	 *
	 * @return string
	 */
	public function get_css_class() {
		return $this->css_class;
	}
}

/**
 * Converts a callback to a IDataTableCellFormatter. Callback must have only one parameter, $column_data.
 * If you need the other parameters you will need to implement the interface instead.
 */
class CallbackCellFormatter implements IDataTableCellFormatter {
	/**
	 * @var callable
	 */
	protected $callable;

	/**
	 * @param $callable callable
	 * @throws Exception
	 */
	public function __construct($callable) {
		if (!is_callable($callable)) {
			throw new Exception("callable must be a callable");
		}

		if ($callable instanceof Closure) {
			// make sure there's one argument
			$reflection = new ReflectionObject($callable);
			if ($reflection->hasMethod('__invoke')) {
				$method = $reflection->getMethod('__invoke');
				if ($method->getNumberOfParameters() !== 1) {
					throw new Exception("callable must have only one parameter");
				}
			}
		}

		$this->callable = $callable;
	}
	public function format($form_name, $column_header, $column_data, $rowid, $state)
	{
		$callable = $this->callable;
		return $callable($column_data);
	}
}

/**
 * Converts a callback to a IDataTableHeaderFormatter. Callback must have only one parameter, $header_data.
 * If you need the other parameters you will need to implement the interface instead.
 */
class CallbackHeaderFormatter implements IDataTableHeaderFormatter {
	/**
	 * @var callback
	 */
	protected $callback;

	/**
	 * @param $callable callable
	 * @throws Exception
	 */
	public function __construct($callable) {
		if (!is_callable($callable)) {
			throw new Exception("callable must be a callable");
		}

		if ($callable instanceof Closure) {
			// make sure there's one argument
			$reflection = new ReflectionObject($callable);
			if ($reflection->hasMethod('__invoke')) {
				$method = $reflection->getMethod('__invoke');
				if ($method->getNumberOfParameters() !== 1) {
					throw new Exception("callable must have only one parameter");
				}
			}
		}

		$this->callable = $callable;
	}

	public function format($form_name, $column_key, $header_data, $state)
	{
		$callable = $this->callable;
		return $callable($header_data);
	}
}