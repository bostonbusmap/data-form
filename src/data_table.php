<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once "data_table_behavior.php";
require_once "data_table_builder.php";
require_once "data_table_button_builder.php";
require_once "data_table_button.php";
require_once "data_table_cell_formatter.php";
require_once "data_table_checkbox.php";
require_once "data_table_checkbox_builder.php";
require_once "data_table_column_builder.php";
require_once "data_table_column.php";
require_once "data_table_header_formatter.php";
require_once "data_table_hidden_builder.php";
require_once "data_table_hidden.php";
require_once "data_table_link_builder.php";
require_once "data_table_link.php";
require_once "data_table_option.php";
require_once "data_table_options_builder.php";
require_once "data_table_options.php";
require_once "data_table_pagination_state.php";
require_once "data_table_radio.php";
require_once "data_table_radio_builder.php";
require_once "data_table_search_formatter.php";
require_once "data_table_search_widget.php";
require_once "data_table_search_widget_builder.php";
require_once "data_table_search_state.php";
require_once "data_table_settings_builder.php";
require_once "data_table_settings.php";
require_once "data_table_textbox_builder.php";
require_once "data_table_textbox.php";
require_once "data_form_state.php";
require_once "data_table_widget.php";
require_once "selected.php";
require_once "sql_builder.php";
require_once "util.php";
require_once "validator_rule.php";

/**
 * This displays an HTML table of data. Rows can be selectable and results are submitted to $form_action
 *
 * Table columns (which must be DataTableColumn) are displayed in order of $columns
 * and are matched with data if the key in $columns matches a key
 * from $sql_field_names or a row in $rows.
 *
 */
class DataTable
{
	/**
	 * @var string Allows for table-specific state. Optional
	 */
	private $table_name;
	/** @var \IDataTableWidget[] Buttons or other pieces of HTML outside the table */
	private $widgets;
	/** @var \DataTableColumn[] Columns for the table */
	private $columns;
	/** @var \string[] You may optionally define this to map field_name -> column_index instead of
	 * putting column keys directly on each row. */
	private $field_names;
	/** @var array The data displayed for this form.
	 * This must be already paginated; what you put here is what will be outputed. */
	private $rows;

	/**
	 * @var string[] Mapping of row id to CSS classes. Can be null, in which case it picks some default row classes
	 */
	private $row_classes;

	/**
	 * @var DataTableSettings Settings for data table's pagination, sorting and filtering
	 */
	private $settings;

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
		if (!($builder instanceof DataTableBuilder)) {
			throw new Exception("builder expected to be instance of DataTableBuilder");
		}
		$this->table_name = $builder->get_table_name();
		$this->widgets = $builder->get_widgets();
		$this->columns = $builder->get_columns();
		$this->field_names = $builder->get_field_names();
		$this->rows = $builder->get_rows();
		$this->row_classes = $builder->get_row_classes();
		$this->settings = $builder->get_settings();
		$this->header = $builder->get_header();
		$this->empty_message = $builder->get_empty_message();
	}

	/**
	 * Returns HTML for table. Meant for use by DataForm, users should call DataForm::display() instead
	 *
	 * May display empty message instead of empty_message is set and there are no rows
	 *
	 * @param string $form_name The name of the form
	 * @param string $form_method Either GET or POST
	 * @param $remote_url string The URL to refresh from via AJAX
	 * @param DataFormState $state Optional state which contains form data. If null defaults are used
	 * @throws Exception
	 * @return string HTML
	 */
	public function display_table($form_name, $form_method, $remote_url, $state = null) {
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
				$ret .= $widget->display($form_name, $form_method, $state) . " ";
			}
		}

		// show blue header on top of table. May contain pagination controls
		if ($this->is_sortable()) {
			$ret .= '<table class="table-autosort">';
		}
		else
		{
			$ret .= '<table>';
		}

		// write pagination controls
		if ($this->header || ($this->settings && $this->settings->uses_pagination())) {
			$ret .= "<caption>";

			if ($this->header) {
				$ret .= $this->header;
			}
			if ($this->settings && $this->settings->uses_pagination())
			{
				$ret .= $this->settings->display_controls($form_name, $form_method, $state,
					$remote_url, $this->table_name);
			}
			$ret .= "</caption>";
		}

		// write header, which may include sorting and filtering HTML
		$ret .= $this->display_table_header($form_name, $form_method, $remote_url, $state);

		// write data
		$ret .= $this->display_table_body($form_name, $form_method, $state);
		$ret .= "</table>";

		// write buttons at bottom of table
		foreach ($this->widgets as $widget) {
			if ($widget->get_placement() == DataTableButton::placement_bottom) {
				$ret .= $widget->display($form_name, $form_method, $state) . " ";
			}
		}

		return $ret;
	}

	/**
	 * Return true if any column is sortable
	 * @return bool
	 */
	public function is_sortable() {
		foreach ($this->columns as $column) {
			if ($column->get_sortable()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return true if any column is filterable
	 * @return bool
	 */
	public function is_searchable() {
		foreach ($this->columns as $column) {
			if ($column->get_searchable()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Return the name of the table if table has a name, or a falsey value
	 *
	 * @return string Name of table if table has a name, or a falsey value
	 */
	public function get_table_name()
	{
		return $this->table_name;
	}

	/** @return string HTML shown in place of table if no text. If falsey, table is shown anyway */
	public function get_empty_message() {
		return $this->empty_message;
	}

	/**
	 * Display header which includes sorting and filtering HTML
	 *
	 * @param $form_name string Name of form
	 * @param $form_method string GET or POST
	 * @param $remote_url string URL to refresh from
	 * @param $state DataFormState State with form information
	 * @return string HTML
	 */
	protected function display_table_header($form_name, $form_method, $remote_url, $state)
	{
		$ret = "<thead>";
		$ret .= "<tr>";

		// figure out sorting state
		/** @var string[] $old_sorting_state mapping of column key to 'asc' or 'desc' */
		$old_sorting_state = array();
		if ($remote_url) {
			foreach ($this->columns as $column) {
				$column_key = $column->get_column_key();

				if ($column->get_sortable()) {
					if ($state && $state->get_sorting_state($column_key, $this->table_name)) {
						$old_sorting_state = array($column_key => $state->get_sorting_state($column_key, $this->table_name));
						break;
					}
				}
			}
			if (!$old_sorting_state) {
				// if nothing was previously set in the state, use the default if any
				if ($this->settings) {
					$old_sorting_state = $this->settings->get_default_sorting();
				}
			}
		}

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
				if ($remote_url) {
					// draw sorting arrow and set hidden field
					$ret .= '<th class="column-' . htmlspecialchars($column_key) . '">';
					if (array_key_exists($column_key, $old_sorting_state)) {
						// set sorting state in form
						// Note that the code at $this->remote is responsible for reading sorting state
						// and doing something useful with it (probably incorporating it into SQL somehow)
						$sorting_name = DataFormState::make_field_name($form_name, DataFormState::get_sorting_state_key($column_key, $this->table_name));
						$ret .= '<input type="hidden" name="' . htmlspecialchars($sorting_name) . '" value="' . htmlspecialchars($old_sorting_state[$column_key]) . '" class="hidden_sorting" />';

						if ($old_sorting_state[$column_key] == DataFormState::sorting_state_asc) {
							$ret .= "&uarr; ";
						}
						elseif ($old_sorting_state[$column_key] == DataFormState::sorting_state_desc) {
							$ret .= "&darr; ";
						}
					}
				}
				else
				{
					// let Javascript handle it
					$ret .= '<th class="column-' . htmlspecialchars($column_key) . ' table-sortable:' . htmlspecialchars($column->get_sortable()) . ' table-sortable" title="Click to sort">';
				}
			}
			else
			{
				// no sorting
				$ret .= '<th class="column-' . htmlspecialchars($column_key) . '">';
			}

			// If sortable, make header text a link which flips sorting
			/** @var DataTableColumn $column */
			if ($column->get_sortable() && $remote_url) {
				// write a link to sort in the opposite direction
				if (array_key_exists($column_key, $old_sorting_state) &&
					$old_sorting_state[$column_key] == DataFormState::sorting_state_desc) {
					$new_sorting_state = DataFormState::sorting_state_asc;
				}
				else
				{
					$new_sorting_state = DataFormState::sorting_state_desc;
				}
				$sorting_state_name = DataFormState::make_field_name($form_name,
					DataFormState::get_sorting_state_key($column_key, $this->table_name));

				$onclick_obj = new DataTableBehaviorClearSortThenRefresh(array($sorting_state_name => $new_sorting_state));
				$onclick = $onclick_obj->action($form_name, $remote_url, $form_method);
				$ret .= '<a onclick="' . htmlspecialchars($onclick) . '">';
			}
			// display special header cell if specified
			$ret .= $column->get_display_header($form_name, $column_key, $state);
			if ($column->get_sortable() && $remote_url) {
				$ret .= "</a>";
			}
			$ret .= "</th>";
		}
		$ret .= "</tr>";

		// if searchable, write a second header row with text fields
		if ($this->is_searchable()) {
			$ret .= "<tr>";
			foreach ($this->columns as $column) {
				$column_key = $column->get_column_key();
				$ret .= "<th>";
				if ($column->get_searchable()) {
					if (!$remote_url) {
						// use javascript to filter via regex
						$ret .= "<input size='8' onkeyup='Table.filter(this, this)' />";
					}
					else
					{
						$default_value = null;
						if ($this->settings) {
							$default_filtering = $this->settings->get_default_filtering();
							if (isset($default_filtering[$column_key])) {
								$default_value = $default_filtering[$column_key];
							}
						}

						$ret .= $column->get_search_formatter()->format($form_name, $remote_url, $form_method, $this->table_name, $column_key,
							$state, $default_value, "");
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
	 * Display the table body HTML
	 *
	 * @param $form_name string Name of form
	 * @param $form_method string GET or POST
	 * @param $state DataFormState State of form
	 * @return string HTML
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

			if ($this->row_classes) {
				if (array_key_exists($row_id, $this->row_classes)) {
					$row_class = $this->row_classes[$row_id];
				}
				else
				{
					$row_class = "";
				}
			} else {
				if ($row_count % 2 == 0) {
					$row_class = "row-even";
				} else {
					$row_class = "row-odd";
				}
			}

			$ret .= '<tr class="' . htmlspecialchars($row_class) . '">';


			// We are writing each column in order and only matching it up with data
			// if the data exists in $row
			foreach ($this->columns as $column) {
				$column_key = $column->get_column_key();
				$ret .= '<td class="column-' . htmlspecialchars($column_key) . '">';
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

	/**
	 * Default settings for the table
	 *
	 * @return DataTableSettings
	 */
	public function get_settings()
	{
		return $this->settings;
	}
}
