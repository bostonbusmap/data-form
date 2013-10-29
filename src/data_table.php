<?php
/**
 * Created by JetBrains PhpStorm.
 * User: george
 * Date: 10/7/13
 * Time: 11:37 AM
 * To change this template use File | Settings | File Templates.
 */

require_once "data_table_behavior.php";
require_once "data_table_builder.php";
require_once "data_table_button.php";
require_once "data_table_cell_formatter.php";
require_once "data_table_checkbox.php";
require_once "data_table_column_builder.php";
require_once "data_table_column.php";
require_once "data_table_header_formatter.php";
require_once "data_table_link.php";
require_once "data_table_options.php";
require_once "data_table_pagination.php";
require_once "data_table_radio.php";
require_once "data_form_state.php";
require_once "data_table_widget.php";

/**
 * This displays a table of data which is also a form.
 * Rows can be selectable and results are submitted to $form_action
 *
 * Table columns (which must be DataTableColumn) are displayed in order of $columns
 * and are matched with data if the key in $columns matches a key
 * from $sql_field_names or a row in $rows.
 *
 * A DataTableColumn is in charge of how to display the column, which may be string data or have a checkbox
 * or select widget or many other things. The user may use callbacks to customize the DataTableColumn
 * See the PHPDoc for DataTableColumn for more information
 *
 */
class DataTable
{
	/**
	 * @var string Allows for table-specific state. Optional
	 */
	private $table_name;
	/** @var \IDataTableWidget[]  */
	private $widgets;
	/** @var \DataTableColumn[]  */
	private $columns;
	/** @var \string[]  */
	private $field_names;
	/** @var array  */
	private $rows;

	/** @var string|bool Either false or a URL to send pagination, sorting, or searching requests to */
	private $remote;

	/**
	 * @var string[] Mapping of row id to CSS classes. Can be null
	 */
	private $row_classes;

	/**
	 * @var DataTablePaginationSettings Pagination settings. If null, no pagination should be done
	 */
	private $pagination_settings;

	/**
	 * @var string HTML to display within header. If null and related items are false, do not display header
	 */
	private $header;

	/** @var string HTML shown in place of table if no text. If falsey, table is shown anyway */
	private $empty_message;

	/**
	 * @param DataTableBuilder $builder
	 * @throws Exception
	 */
	public function __construct($builder) {
		$this->table_name = $builder->get_table_name();
		$this->widgets = $builder->get_widgets();
		$this->columns = $builder->get_columns();
		$this->field_names = $builder->get_field_names();
		$this->rows = $builder->get_rows();
		$this->remote = $builder->get_remote();
		$this->row_classes = $builder->get_row_classes();
		$this->pagination_settings = $builder->get_pagination_settings();
		$this->header = $builder->get_header();
		$this->empty_message = $builder->get_empty_message();
	}

	/**
	 * Returns HTML for table. This is useful if sending it via ajax to populate a div
	 *
	 * May display empty message instead of empty_message is set and there are no rows
	 *
	 * @param string $form_name
	 * @param string $form_method Either GET or POST
	 * @param DataFormState $state
	 * @throws Exception
	 * @return string HTML
	 */
	public function display_table($form_name, $form_method, $state=null) {
		if (!is_string($form_name)) {
			throw new Exception("form_name must be a string");
		}
		if (strtolower($form_method) != "get" && strtolower($form_method) != "post") {
			throw new Exception("form_method must be GET or POST");
		}
		if ($state && !($state instanceof DataFormState)) {
			throw new Exception("state must be instance of DataFormState");
		}
		$ret = "";

		if (!$this->rows && $this->empty_message) {
			return $this->empty_message;
		}

		// display top buttons
		foreach ($this->widgets as $widget) {
			if ($widget->get_placement() == DataTableButton::placement_top) {
				$ret .= $widget->display($form_name, $form_method, $state);
			}
		}

		// show blue header on top of table. May contain pagination controls
		if ($this->is_sortable()) {
			$ret .= "<table class='table-autosort table-stripeclass:shadedbg table-altstripeclass:shadedbg' style='width: 400px;'>";
		}
		else
		{
			$ret .= "<table class='table-stripeclass:shadedbg table-altstripeclass:shadedbg' style='width: 400px;'>";
		}
		if ($this->header || $this->pagination_settings) {
			$ret .= "<caption>";

			if ($this->header) {
				$ret .= $this->header;
			}
			if ($this->pagination_settings)
			{
				$ret .= $this->pagination_settings->display_controls($form_name, $form_method, $state,
					$this->remote, $this->table_name);
			}
			$ret .= "</caption>";
		}

		$ret .= $this->display_table_header($form_name, $form_method, $state);

		$ret .= $this->display_table_body($form_name, $form_method, $state);
		$ret .= "</table>";

		// write buttons at bottom of table
		foreach ($this->widgets as $widget) {
			if ($widget->get_placement() == DataTableButton::placement_bottom) {
				$ret .= $widget->display($form_name, $form_method, $state);
			}
		}

		return $ret;
	}

	public function is_sortable() {
		foreach ($this->columns as $column) {
			if ($column->get_sortable()) {
				return true;
			}
		}
		return false;
	}

	public function is_searchable() {
		foreach ($this->columns as $column) {
			if ($column->get_searchable()) {
				return true;
			}
		}
		return false;
	}

	public function get_table_name()
	{
		return $this->table_name;
	}

	/** @return string HTML shown in place of table if no text. If falsey, table is shown anyway */
	public function get_empty_message() {
		return $this->empty_message;
	}

	/**
	 * @param $form_name string
	 * @param $form_method string GET or POST
	 * @param $state DataFormState
	 * @return string HTML
	 */
	protected function display_table_header($form_name, $form_method, $state)
	{
		$ret = "<thead>";
		$ret .= "<tr class='standard-table-header'>";
		// TODO: replace with DOMDocument so we don't have to worry about sanitizing HTML

		// write out header cells

		// The most significant field here is $this->remote. If falsey, the table is strictly browser based
		// with sorting and searching done via Javascript.
		// If $this->remote is not falsey, it's a string pointing to the AJAX url
		// which it contacts with sorting, searching and pagination data. Entire div is refreshed at once.
		foreach ($this->columns as $column) {
			$column_key = $column->get_column_key();

			// if an AJAX form, get_sortable is treated like a boolean
			// if a local form, get_sortable may have the string which says what kind of sorting will be done
			// either 'numeric' or 'alphanumeric'
			if ($column->get_sortable()) {
				if ($this->remote) {
					// draw sorting arrow and set hidden field
					$ret .= "<th class='column_" . $column_key . "'>";
					if ($state) {
						// set sorting state in form
						// Note that the code at $this->remote is responsible for reading sorting state
						// and doing something useful with it (probably incorporating it into SQL somehow)
						$old_sorting_state = $state->get_sorting_state($column_key, $this->table_name);
						$sorting_name = DataFormState::make_field_name($form_name, DataFormState::get_sorting_state_key($column_key, $this->table_name));
						$ret .= "<input type='hidden' name='$sorting_name' value='$old_sorting_state' />";
					}
					else
					{
						$old_sorting_state = null;
					}

					if ($old_sorting_state == DataFormState::sorting_state_asc) {
						$ret .= "&uarr; ";
					}
					elseif ($old_sorting_state == DataFormState::sorting_state_desc) {
						$ret .= "&darr; ";
					}
				}
				else
				{
					// let Javascript handle it
					$ret .= "<th class='column_" . $column_key . " table-sortable:" . $column->get_sortable() . " table-sortable' title='Click to sort'>";
				}
			}
			else
			{
				// no sorting
				$ret .= "<th class='column_" . $column_key . "'>";
			}

			// If sortable, make header text a link which flips sorting
			/** @var DataTableColumn $column */
			if ($column->get_sortable() && $this->remote) {
				if ($state && $state->get_sorting_state($column_key)) {
					$old_sorting_state = $state->get_sorting_state($column_key);
				}
				else
				{
					// not really true but it provides a default
					$old_sorting_state = DataFormState::sorting_state_asc;
				}

				if ($old_sorting_state == DataFormState::sorting_state_asc) {
					$new_sorting_state = DataFormState::sorting_state_desc;
				}
				else
				{
					$new_sorting_state = DataFormState::sorting_state_asc;
				}
				$sorting_state_name = DataFormState::make_field_name($form_name,
					DataFormState::get_sorting_state_key($column_key, $this->table_name));
				$sort_string = "&" . $sorting_state_name . "=" . $new_sorting_state;

				$onclick_obj = new DataTableBehaviorRefresh($sort_string);
				$onclick = $onclick_obj->action($form_name, $this->remote, $form_method);
				$ret .= "<a onclick='$onclick'>";
			}
			// display special header cell if specified
			$ret .= $column->get_display_header($form_name, $column_key);
			if ($column->get_sortable() && $this->remote) {
				$ret .= "</a>";
			}
			$ret .= "</th>";
		}
		$ret .= "</tr>";

		// if searchable, write a second header row with text fields
		if ($this->is_searchable()) {
			$ret .= "<tr class='standard-table-header'>";
			foreach ($this->columns as $column) {
				$column_key = $column->get_column_key();
				$ret .= "<th>";
				if ($column->get_searchable()) {
					if (!$this->remote) {
						// use javascript to filter via regex
						$ret .= "<input size='8' onkeyup='Table.filter(this, this)' />";
					}
					else
					{
						// set searching state then call for a refresh
						// It's up to code at $this->remote to specify how searching state is used to filter
						if ($state && $state->get_searching_state($column_key)) {
							$old_searching_state = $state->get_searching_state($column_key);
						}
						else
						{
							$old_searching_state = "";
						}
						$searching_name = DataFormState::make_field_name($form_name,
							DataFormState::get_searching_state_key($column_key, $this->table_name));
						$ret .= "<input size='8' name='" . $searching_name . "' value='" . $old_searching_state . "' />";
					}
				}
				$ret .= "</th>";
			}
			$ret .= "</tr>";
		}
		$ret .= "</thead>";
		return $ret;
	}

	/**
	 * @param $form_name string Name of form
	 * @param $form_method string GET or POST
	 * @param $state DataFormState
	 * @return string
	 * @throws Exception
	 */
	public function display_table_body($form_name, $form_method, $state)
	{
		$ret = "<tbody>";

		// user can either have field names as keys for each row, or set them in $this->sql_field_names
		// and map them to indexes
		$indexes = array();
		$count = 0;
		foreach ($this->field_names as $field_name) {
			$indexes[$field_name] = $count;
			$count++;
		}


		// end of header, start writing cells
		$row_count = 0;
		foreach ($this->rows as $row_id => $row) {
			if (!is_array($row)) {
				throw new Exception("Each row in rows expected to be an array");
			}
			$row_id = (string)$row_id;

			if ($this->row_classes && array_key_exists($row_id, $this->row_classes)) {
				$row_class = $this->row_classes[$row_id];
			} else {
				if ($row_count % 2 == 0) {
					$row_class = "shadedbg";
				} else {
					$row_class = "unshadedbg";
				}
			}

			$ret .= "<tr class='$row_class'>";


			// We are writing each column in order and only matching it up with data
			// if the data exists in $row
			foreach ($this->columns as $column) {
				$column_key = $column->get_column_key();
				$col_css = $column->get_css();
				$ret .= "<td class='column_$column_key $col_css'>";
				/** @var DataTableColumn $column */
				if (array_key_exists($column_key, $row)) {
					$cell = $row[$column_key];
				} elseif (array_key_exists($column_key, $indexes)) {
					$index = $indexes[$column_key];
					if ($index >= count($row)) {
						throw new Exception("Tried to get index $index of row with " . count($row) . " columns");
					}
					if (array_key_exists($index, $row)) {
						$cell = $row[$index];
					} else {
						$cell = null;
					}
				} else {
					// a column where there is no data is a common case, for instance
					// the row selection checkbox
					$cell = null;
				}
				$ret .= $column->get_display_data($form_name, $column_key, $cell, $row_id, $state);
				$ret .= "</td>";
			}

			$ret .= "</tr>";
			$row_count++;
		}
		$ret .= "</tbody>";
		return $ret;
	}
}
