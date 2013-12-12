<?php
class DataTableColumnBuilder {
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
	 * @var IDataTableSearchFormatter Renders HTML used for searching a column
	 */
	protected $search_formatter;
	/**
	 * @var string Column header meant to be displayed
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

	public static function create() {
		return new DataTableColumnBuilder();
	}

	/**
	 * @param $header_formatter IDataTableHeaderFormatter
	 * @return DataTableColumnBuilder
	 */
	public function header_formatter($header_formatter) {
		$this->header_formatter = $header_formatter;
		return $this;
	}

	/**
	 * @param $cell_formatter IDataTableCellFormatter
	 * @return DataTableColumnBuilder
	 */
	public function cell_formatter($cell_formatter) {
		$this->cell_formatter = $cell_formatter;
		return $this;
	}

	/**
	 * @param $sortable string|bool
	 * @return DataTableColumnBuilder
	 */
	public function sortable($sortable) {
		$this->sortable = $sortable;
		return $this;
	}

	/**
	 * @param $searchable bool
	 * @return DataTableColumnBuilder
	 */
	public function searchable($searchable) {
		$this->searchable = $searchable;
		return $this;
	}

	/**
	 * @param $search_formatter IDataTableSearchFormatter
	 * @return DataTableColumnBuilder
	 */
	public function search_formatter($search_formatter) {
		$this->search_formatter = $search_formatter;
		return $this;
	}

	/**
	 * @param $display_header_name string
	 * @return DataTableColumnBuilder
	 */
	public function display_header_name($display_header_name) {
		$this->display_header_name = $display_header_name;
		return $this;
	}

	/**
	 * @param $column_key string
	 * @return DataTableColumnBuilder
	 */
	public function column_key($column_key) {
		$this->column_key = $column_key;
		return $this;
	}

	/**
	 * @param $css string
	 * @return DataTableColumnBuilder
	 */
	public function css($css) {
		$this->css = $css;
		return $this;
	}

	public function get_header_formatter() {
		return $this->header_formatter;
	}

	public function get_cell_formatter() {
		return $this->cell_formatter;
	}

	public function get_sortable() {
		return $this->sortable;
	}

	public function get_searchable() {
		return $this->searchable;
	}

	public function get_search_formatter() {
		return $this->search_formatter;
	}

	public function get_display_header_name() {
		return $this->display_header_name;
	}

	public function get_column_key() {
		return $this->column_key;
	}

	public function get_css() {
		return $this->css;
	}

	/**
	 * Validates input and creates DataTableColumn
	 *
	 * @return DataTableColumn
	 * @throws Exception
	 */
	public function build() {
		if (is_null($this->header_formatter)) {
			$this->header_formatter = new DefaultHeaderFormatter();
		}
		if (!($this->header_formatter instanceof IDataTableHeaderFormatter))
		{
			throw new Exception("header_formatter must be instance of IDataTableHeaderFormatter");
		}

		if (is_null($this->cell_formatter)) {
			$this->cell_formatter = new DefaultCellFormatter();
		}
		if (!($this->cell_formatter instanceof IDataTableCellFormatter)) {
			throw new Exception("cell_formatter must be instance of IDataTableHeaderFormatter");
		}

		if (is_null($this->sortable)) {
			$this->sortable = false;
		}
		if ($this->sortable === true) {
			$this->sortable = "numeric";
		}
		if ($this->sortable && !is_string($this->sortable)) {
			throw new Exception("If sortable is true, it must be a string which is the CSS class for how it's sorted (for example, numeric)");
		}
		if (!$this->sortable && !is_bool($this->sortable)) {
			throw new Exception("If sortable is false, it must be a bool");
		}

		if (is_null($this->searchable)) {
			$this->searchable = false;
		}
		if (!is_bool($this->searchable)) {
			throw new Exception("searchable must be a bool");
		}

		if (is_null($this->search_formatter)) {
			$this->search_formatter = new TextboxSearchFormatter(DataTableSearchState::like);
		}
		if (!($this->search_formatter instanceof IDataTableSearchFormatter)) {
			throw new Exception("search_formatter must be instance of IDataTableSearchFormatter");
		}

		if (is_null($this->column_key)) {
			$this->column_key = "";
		}
		if (!is_string($this->column_key)) {
			throw new Exception("column_key must be a string");
		}
		if (is_null($this->display_header_name)) {
			$this->display_header_name = "";
		}
		if (!is_string($this->display_header_name)) {
			throw new Exception("display_header_name must be a string");
		}
		if (is_null($this->css)) {
			$this->css = "";
		}
		if (!is_string($this->css)) {
			throw new Exception("css must be a string");
		}

		return new DataTableColumn($this);
	}
}