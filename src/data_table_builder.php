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
	 * This is like the constructor but allows for chaining of methods
	 * (ie, DataTableBuilder::create_builder()->buttons($buttons)->build())
	 * @return DataTableBuilder
	 */
	public static function create() {
		return new DataTableBuilder();
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
