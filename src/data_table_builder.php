<?php
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
	/** @var array Mapping of row_id => row. row is array of items with either field name keys or index keys */
	private $rows;

	/** @var string|bool If this is a string then sorting, searching, and pagination options are sent to this URL
	 * If false sorting and searching are done locally */
	private $remote;

	/**
	 * @var string[] Mapping of row id to CSS classes
	 */
	private $row_classes;
	/**
	 * @var DataTablePaginationSettings Pagination settings. If null, no pagination should be done
	 */
	private $pagination_settings;
	/**
	 * @var string HTML shown for header
	 */
	private $header;

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
	 * Mapping of row_id => row. row is array of items with either field name keys or index keys
	 *
	 * @param array $rows
	 * @return DataTableBuilder
	 */
	public function rows($rows)
	{
		$this->rows = $rows;
		return $this;
	}

	/**
	 * If this is a string then sorting, searching, and pagination options are sent to this URL
	 * If false sorting and searching are done locally
	 *
	 * @param bool|string $remote
	 * @return DataTableBuilder
	 */
	public function remote($remote)
	{
		$this->remote = $remote;
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
	 * @param $pagination_settings DataTablePaginationSettings  If null, no pagination should be done
	 * @return DataTableBuilder
	 */
	public function pagination_settings($pagination_settings) {
		$this->pagination_settings = $pagination_settings;
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
	 * @return bool|string If this is a string then sorting, searching, and pagination options are sent to this URL
	 * If false sorting and searching are done locally
	 */
	public function get_remote() {
		return $this->remote;
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
	 * @return DataTablePaginationSettings  If null, no pagination should be done
	 */
	public function get_pagination_settings() {
		return $this->pagination_settings;
	}

	/**
	 * @return string
	 */
	public function get_header() {
		return $this->header;
	}

	/**
	 * Constructs a DataTable
	 *
	 * @return DataTable
	 * @throws Exception
	 */
	public function build() {
		// if unspecified, set options here

		if (!$this->table_name) {
			$this->table_name = "";
		}
		if (!is_string($this->table_name)) {
			throw new Exception("table_name must be a string");
		}

		if (!$this->columns) {
			throw new Exception("columns must have at least one column");
		}
		if (!is_array($this->columns)) {
			throw new Exception("columns must be an array of DataTableColumn");
		}
		foreach ($this->columns as $column) {
			if (!($column instanceof DataTableColumn)) {
				throw new Exception("Each column must be instance of DataTableColumn.");
			}
		}

		if (!$this->widgets) {
			$this->widgets = array();
		}
		if (!is_array($this->widgets)) {
			throw new Exception("buttons must be an array of IDataTableWidget");
		}
		foreach ($this->widgets as $button) {
			if (!($button instanceof IDataTableWidget)) {
				throw new Exception("Each button must be instance of IDataTableWidget");
			}
		}

		if (!$this->field_names) {
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

		if (!$this->rows) {
			// TODO: show special message saying there's no data to display
			$this->rows = array();
		}
		if (!is_array($this->rows)) {
			throw new Exception("rows must be an array of arrays, the data to display in the table");
		}
		foreach ($this->rows as $row) {
			if (!is_array($row)) {
				throw new Exception("Each row in rows must be an array");
			}
		}

		if (!$this->remote) {
			$this->remote = false;
		}
		if ($this->remote && !is_string($this->remote)) {
			throw new Exception("remote must be a string which is the URL the form refreshes from");
		}

		if (!$this->row_classes) {
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

		if (!$this->header) {
			$this->header = "";
		}
		if (!is_string($this->header)) {
			throw new Exception("header must be a string containing HTML to display above the table");
		}

		if ($this->pagination_settings && !($this->pagination_settings instanceof DataTablePaginationSettings)) {
			throw new Exception("pagination_settings must be instance of DataTablePaginationSettings");
		}

		return new DataTable($this);
	}
}
