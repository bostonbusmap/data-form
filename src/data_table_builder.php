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
	private $buttons;
	/** @var \DataTableColumn[] Mapping of SQL field name to DataTableColumn object */
	private $columns;
	/** @var \string[] Array of field names which correspond to each row item */
	private $sql_field_names;
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
	 * @var bool whether to show pagination selection items
	 */
	private $show_pagination_controls;
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
	 * @param \IDataTableWidget[] $buttons
	 * @return DataTableBuilder
	 * @throws Exception
	 */
	public function buttons($buttons)
	{
		$this->buttons = $buttons;
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
	 * @param \string[] $sql_field_names
	 * @return DataTableBuilder
	 */
	public function sql_field_names($sql_field_names)
	{
		$this->sql_field_names = $sql_field_names;
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
	 * @param $show_pagination_controls bool
	 * @return DataTableBuilder
	 */
	public function show_pagination_controls($show_pagination_controls) {
		$this->show_pagination_controls = $show_pagination_controls;
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
	public function get_buttons() {
		return $this->buttons;
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
	public function get_sql_field_names() {
		return $this->sql_field_names;
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
	 * @return bool
	 */
	public function get_show_pagination_controls() {
		return $this->show_pagination_controls;
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

		if (!$this->sql_field_names) {
			// make sure this is an array
			$this->sql_field_names = array();
		}

		if (!$this->columns) {
			throw new Exception("columns must have at least one column");
		}
		foreach ($this->columns as $column) {
			if (!($column instanceof DataTableColumn)) {
				throw new Exception("Each column must be instance of DataTableColumn.");
			}
		}

		if (!$this->buttons) {
			$this->buttons = array();
		}
		foreach ($this->buttons as $button) {
			if (!($button instanceof IDataTableWidget)) {
				throw new Exception("Each button must be instance of IDataTableWidget");
			}
		}

		return new DataTable($this);
	}
}
