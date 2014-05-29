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
 * Builder for DataTableColumn
 */
class DataTableColumnBuilder {
	/**
	 * @var IDataTableHeaderFormatter Callback to format header data for display
	 */
	protected $header_formatter;
	/**
	 * @var IDataTableHeaderFormatter Callback to format footer data for display
	 */
	protected $footer_formatter;
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
	 * @var string Footer HTML for this column, if any
	 */
	protected $display_footer_name;

	/**
	 * @var string Key which matches column to data
	 */
	protected $column_key;

	/**
	 * @var string CSS class for each cell in column, if any
	 */
	protected $css_class;

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
	 * @param $footer_formatter IDataTableHeaderFormatter
	 * @return DataTableColumnBuilder
	 */
	public function footer_formatter($footer_formatter) {
		$this->footer_formatter = $footer_formatter;
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
	 * @param $display_footer_name string
	 * @return DataTableColumnBuilder
	 */
	public function display_footer_name($display_footer_name) {
		$this->display_footer_name = $display_footer_name;
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
	 * @param $css_class string
	 * @return DataTableColumnBuilder
	 */
	public function css_class($css_class) {
		$this->css_class = $css_class;
		return $this;
	}

	public function get_header_formatter() {
		return $this->header_formatter;
	}

	public function get_footer_formatter() {
		return $this->footer_formatter;
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

	public function get_display_footer_name() {
		return $this->display_footer_name;
	}

	public function get_column_key() {
		return $this->column_key;
	}

	public function get_css_class() {
		return $this->css_class;
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
		if (is_null($this->footer_formatter)) {
			$this->footer_formatter = new DefaultHeaderFormatter();
		}
		if (!($this->footer_formatter instanceof IDataTableHeaderFormatter)) {
			throw new Exception("footer_formatter must be instance of IDataTableHeaderFormatter");
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
			$this->display_header_name = (string)$this->display_header_name;
		}

		if (is_null($this->display_footer_name)) {
			$this->display_footer_name = "";
		}
		if (!is_string($this->display_footer_name)) {
			$this->display_footer_name = (string)$this->display_footer_name;
		}

		if ($this->css_class === null) {
			$this->css_class = "";
		}
		if (!is_string($this->css_class)) {
			throw new Exception("css_class must be a string");
		}

		return new DataTableColumn($this);
	}
}