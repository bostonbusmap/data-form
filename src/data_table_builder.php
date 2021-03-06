<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

/**
 * Builder class for DataTable. A Builder class provides arguments to another class in a nice way without
 * requiring all of the arguments be listed on the same statement. Example:
 *
 * $builder = DataTableBuilder::create()->buttons($buttons)->columns($columns);
 * $data_table = $builder->build();
 * $data_form = DataFormBuilder::create("form_name")->tables(array($data_table))->build();
 * echo $data_form->display();
 */
class DataTableBuilder {
	/** @var  string */
	private $table_name;
	/** @var \IDataTableWidget[] Buttons to display which submit or reset the form */
	private $widgets;
	/** @var \DataTableColumn[] Mapping of SQL field name to DataTableColumn object */
	private $columns;
	/** @var \string[] Array of field names which correspond to each row item */
	private $field_names;
	/** @var array|Traversable Mapping of row_id => row. row is array of items with either field name keys or index keys */
	private $rows;

	/**
	 * @var string[] Mapping of row id to CSS classes
	 */
	private $row_classes;
	/**
	 * @var DataTableSettings Settings for pagination, filtering and sorting. May be null
	 */
	private $settings;
	/**
	 * @var string HTML shown for header
	 */
	private $header;

	/** @var string HTML shown in place of table if no text. If falsey, table is shown anyway */
	private $empty_message;

	/**
	 * @var string The column key of the selected items, or null to disable
	 */
	private $selected_items_column;

	/**
	 * @var string If displaying as a widget of another table, placement next to the containing table.
	 */
	private $placement;

	/**
	 * This is like the constructor but allows for chaining of methods
	 * (ie, DataTableBuilder::create_builder()->buttons($buttons)->build())
	 * @return DataTableBuilder
	 */
	public static function create() {
		return new DataTableBuilder();
	}

	/**
	 * @param $table_name string Name of table. Differentiates two tables in same form. Optional.
	 * @return DataTableBuilder
	 */
	public function table_name($table_name) {
		$this->table_name = $table_name;
		return $this;
	}

	/**
	 * Buttons to display which submit or reset the form
	 *
	 * @param \IDataTableWidget[] $widgets
	 * @return DataTableBuilder
	 * @throws Exception
	 */
	public function widgets($widgets)
	{
		$this->widgets = $widgets;
		return $this;
	}

	/**
	 * Mapping of SQL field name to DataTableColumn object
	 *
	 * @param \DataTableColumn[] $columns
	 * @return DataTableBuilder
	 * @throws Exception
	 */
	public function columns($columns)
	{
		$this->columns = $columns;
		return $this;
	}

	/**
	 * Array of field names which correspond to each row item. NOTE: if the field names are keys for each row
	 * (if you used fetch_assoc) then you don't need to use this.
	 *
	 * @param \string[] $field_names
	 * @return DataTableBuilder
	 */
	public function field_names($field_names)
	{
		$this->field_names = $field_names;
		return $this;
	}

	/**
	 * Mapping of row_id => row. row is array of items with either field name keys or index keys.
	 *
	 * If $rows is a Traversable, it will be converted to an array internally during build()
	 *
	 * @param array|Traversable $rows
	 * @return DataTableBuilder
	 */
	public function rows($rows)
	{
		$this->rows = $rows;
		return $this;
	}

	/**
	 * @param $row_classes string[]
	 * @return DataTableBuilder
	 */
	public function row_classes($row_classes) {
		$this->row_classes = $row_classes;
		return $this;
	}

	/**
	 * @param $settings DataTableSettings
	 * @return DataTableBuilder
	 */
	public function settings($settings) {
		$this->settings = $settings;
		return $this;
	}

	/**
	 * @param $header string
	 * @return DataTableBuilder
	 */
	public function header($header) {
		$this->header = $header;
		return $this;
	}

	/**
	 * @param $empty_message string HTML shown in place of table if no text. If falsey, table is shown anyway
	 * @return DataTableBuilder
	 */
	public function empty_message($empty_message) {
		$this->empty_message = $empty_message;
		return $this;
	}

	/**
	 * @param $selected_items_column string|null The column key of the selected items, or null to disable
	 * @return DataTableBuilder
	 */
	public function selected_items_column($selected_items_column) {
		$this->selected_items_column = $selected_items_column;
		return $this;
	}

	/**
	 * @param $placement string If displaying as a widget of another table, placement next to the containing table.
	 * @return DataTableBuilder
	 */
	public function placement($placement) {
		$this->placement = $placement;
		return $this;
	}

	/**
	 * @return string Name of table
	 */
	public function get_table_name() {
		return $this->table_name;
	}

	/**
	 * @return IDataTableWidget[] Buttons to display which submit or reset the form
	 */
	public function get_widgets() {
		return $this->widgets;
	}

	/**
	 * @return DataTableColumn[] Mapping of SQL field name to DataTableColumn object
	 */
	public function get_columns() {
		return $this->columns;
	}

	/**
	 * @return string[] Array of field names which correspond to each row item
	 */
	public function get_field_names() {
		return $this->field_names;
	}

	/**
	 * @return array Mapping of row_id => row. row is array of items with either field name keys or index keys
	 */
	public function get_rows() {
		return $this->rows;
	}

	/**
	 * Mapping of row id to CSS classes
	 *
	 * @return string[]
	 */
	public function get_row_classes()
	{
		return $this->row_classes;
	}

	/**
	 * Default settings for table
	 *
	 * @return DataTableSettings
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Text for header of table
	 * @return string
	 */
	public function get_header() {
		return $this->header;
	}

	/**
	 * Empty message if table has no rows
	 * @return string
	 */
	public function get_empty_message() {
		return $this->empty_message;
	}

	/**
	 * @return string The column key for selected items, or null if not shown
	 */
	public function get_selected_items_column() {
		return $this->selected_items_column;
	}

	public function get_placement() {
		return $this->placement;
	}

	/**
	 * Validates input and constructs a DataTable
	 *
	 * @return DataTable
	 * @throws Exception
	 */
	public function build() {
		// if unspecified, set options here

		if (is_null($this->table_name)) {
			$this->table_name = "";
		}
		if (!is_string($this->table_name)) {
			throw new Exception("table_name must be a string");
		}

		if (is_null($this->columns)) {
			$this->columns = array();
		}
		if (!is_array($this->columns)) {
			throw new Exception("columns must be an array of DataTableColumn");
		}
		foreach ($this->columns as $column) {
			if (!($column instanceof DataTableColumn)) {
				throw new Exception("Each column must be instance of DataTableColumn.");
			}
		}

		if (is_null($this->widgets)) {
			$this->widgets = array();
		}
		if (!is_array($this->widgets)) {
			throw new Exception("buttons must be an array of IDataTableWidget");
		}
		$this->widgets = array_map(function($widget) {
			if (is_callable($widget)) {
				$widget = new CallbackWidget($widget);
			}
			elseif (!($widget instanceof IDataTableWidget)) {
				throw new Exception("Each widget must be instance of IDataTableWidget or a callback");
			}

			return $widget;
		}, $this->widgets);

		if (is_null($this->field_names)) {
			// make sure this is an array
			$this->field_names = array();
		}
		if (!is_array($this->field_names)) {
			throw new Exception("sql_field_names must be an array of strings corresponding to field names");
		}
		foreach ($this->field_names as $field_name) {
			if (!is_string($field_name)) {
				throw new Exception("Each item in sql_field_names must be a string");
			}
		}

		if (is_null($this->rows)) {
			$this->rows = array();
		}
		if (!is_array($this->rows) && !($this->rows instanceof Iterator)) {
			throw new Exception("rows must be an array or an Iterator");
		}

		if (is_null($this->row_classes)) {
			$this->row_classes = array();
		}
		if (!is_array($this->row_classes)) {
			throw new Exception("row_classes must be an array");
		}
		foreach ($this->row_classes as $row_class) {
			if (!is_string($row_class)) {
				throw new Exception("row_classes must be an array of strings which are CSS classes");
			}
		}

		if (is_null($this->header)) {
			$this->header = "";
		}
		if (!is_string($this->header)) {
			throw new Exception("header must be a string containing HTML to display above the table");
		}

		if ($this->settings && !($this->settings instanceof DataTableSettings)) {
			throw new Exception("settings must be instance of DataTableSettings");
		}

		if (is_null($this->empty_message)) {
			$this->empty_message = "";
		}
		if (!is_string($this->empty_message)) {
			throw new Exception("empty_message must be a string containing HTML");
		}

		if ($this->selected_items_column === null) {
			$this->selected_items_column = "";
		}
		if (!is_string($this->selected_items_column)) {
			throw new Exception("selected_items_column must be a string column key");
		}

		if ($this->placement === null) {
			$this->placement = IDataTableWidget::placement_top;
		}
		if ($this->placement !== IDataTableWidget::placement_top &&
			$this->placement !== IDataTableWidget::placement_bottom) {
			throw new Exception("placement must be 'top' or 'bottom'");
		}

		return new DataTable($this);
	}
}
