<?php
/**
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once "array_manager.php";
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
require_once "data_table_sorting_state.php";
require_once "data_table_sorting_formatter.php";
require_once "data_table_textarea_builder.php";
require_once "data_table_textarea.php";
require_once "data_table_textbox_builder.php";
require_once "data_table_textbox.php";
require_once "data_form_state.php";
require_once "data_table_widget.php";
require_once "pagination_info.php";
require_once "paginator.php";
require_once "selected.php";
require_once "sql_builder.php";
require_once "util.php";
require_once "validator_rule.php";
require_once "writer.php";

/**
 * This displays an HTML table of data. Rows can be selectable and results are submitted to $form_action
 *
 * Table columns (which must be DataTableColumn) are displayed in order of $columns
 * and are matched with data if the key in $columns matches a key
 * from $sql_field_names or a row in $rows.
 *
 */
class DataTable implements IDataTableWidget
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
	 * @var string column key for selected items or empty string to disable
	 */
	protected $selected_items_column;

	/**
	 * @var string If contained in another table, placement relative to that table
	 */
	protected $placement;

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
		$this->selected_items_column = $builder->get_selected_items_column();
		$this->placement = $builder->get_placement();
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
		$writer = new StringWriter();
		$this->display_table_using_writer($form_name, $form_method, $remote_url, $writer, $state);
		return $writer->get_contents();
	}

	/**
	 * Returns HTML for table. Meant for use by DataForm, users should call DataForm::display() instead
	 *
	 * May display empty message instead of empty_message is set and there are no rows
	 *
	 * @param string $form_name The name of the form
	 * @param string $form_method Either GET or POST
	 * @param $remote_url string The URL to refresh from via AJAX
	 * @param IWriter $writer Writer to output HTML
	 * @param DataFormState $state Optional state which contains form data. If null defaults are used
	 * @throws Exception
	 * @return void
	 */
	public function display_table_using_writer($form_name, $form_method, $remote_url, $writer, $state = null) {
		if (!is_string($form_name)) {
			throw new Exception("form_name must be a string");
		}
		if (strtolower($form_method) != "get" && strtolower($form_method) != "post") {
			throw new Exception("form_method must be GET or POST");
		}
		if ($state && !($state instanceof DataFormState)) {
			throw new Exception("state must be instance of DataFormState");
		}

		// If rows is empty and it's not empty just because we're filtering them, then display the empty message if it exists
		if (!$this->rows) {
			if ($this->empty_message && ($state === null || !$state->has_item(array(DataFormState::state_key, DataFormState::searching_state_key)))) {
				$writer->write($this->empty_message);
				return;
			}
			else
			{
				$this->rows = array();
			}
		}

		// display top buttons
		foreach ($this->widgets as $widget) {
			if ($widget->get_placement() == DataTableButton::placement_top) {
				$writer->write($widget->display($form_name, $form_method, $remote_url, $state) . " ");
			}
		}

		// show blue header on top of table. May contain pagination controls
		if ($this->is_sortable()) {
			$writer->write('<table class="table-autosort"');
		}
		else
		{
			$writer->write('<table');
		}
		if ($this->table_name) {
			$writer->write(' id="' . htmlspecialchars($this->table_name) . '_table"');
		}
		$writer->write('>');

		// write pagination controls
		if ($this->header !== "" ||
			($this->settings && $this->settings->uses_pagination()) ||
			$this->selected_items_column !== "") {
			$writer->write("<caption>");

			if ($this->header !== "") {
				$writer->write($this->header);
			}
			if ($this->selected_items_column !== "") {
				if ($state !== null) {
					$items = $state->find_item(array($this->selected_items_column));
					if (is_array($items)) {
						$selected_items_count = count($items);
					}
					elseif ($items !== null)
					{
						throw new Exception("selected_items_column is not an array");
					}
					else
					{
						$selected_items_count = 0;
					}

					if ($this->settings->get_total_rows() === null) {
						$selected_items_header = "<div style=\"text-align: left\">Selected items: " . $selected_items_count . "</div>";
					}
					else
					{
						$selected_items_header = "<div style=\"text-align: left\">Selected items: " . $selected_items_count . " out of " . $this->settings->get_total_rows() . "</div>";
					}
					$writer->write($selected_items_header);
				}
			}
			if ($this->settings && $this->settings->uses_pagination())
			{
				$writer->write($this->settings->display_controls($form_name, $form_method, $state,
					$remote_url, $this->table_name));
			}
			$writer->write("</caption>");
		}

		// write header, which may include sorting and filtering HTML
		$writer->write($this->display_table_header($form_name, $form_method, $remote_url, $state));

		// write data
		$this->display_table_body($form_name, $form_method, $remote_url, $state, $writer);
		$writer->write($this->display_table_footer($form_name, $form_method, $remote_url, $state));
		$writer->write("</table>");

		// write buttons at bottom of table
		foreach ($this->widgets as $widget) {
			if ($widget->get_placement() == DataTableButton::placement_bottom) {
				$writer->write($widget->display($form_name, $form_method, $remote_url, $state) . " ");
			}
		}
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
	 * Display footer
	 *
	 * @param $form_name string Name of form
	 * @param $form_method string GET or POSt
	 * @param $remote_url string URL to refresh from
	 * @param $state DataFormState State with form information
	 * @return string HTML
	 */
	protected function display_table_footer($form_name, $form_method, $remote_url, $state) {
		$row = array();
		$do_display = false;
		foreach ($this->columns as $column) {
			$column_key = $column->get_column_key();
			$value = $column->get_display_footer($form_name, $column_key, $state);
			if ($value) {
				$do_display = true;
			}
			$row[$column_key] = $value;
		}

		$ret = "";
		if ($do_display) {
			$ret .= "<tfoot>";
			foreach ($row as $k => $v) {
				$ret .= "<th>";
				$ret .= $v;
				$ret .= "</th>";
			}
			$ret .= "</tfoot>";
		}
		return $ret;
	}

	/**
	 * Display header which includes sorting and filtering HTML
	 *
	 * @param $form_name string Name of form
	 * @param $form_method string GET or POST
	 * @param $remote_url string URL to refresh from
	 * @param $state DataFormState State with form information
	 * @throws Exception
	 * @return string HTML
	 */
	protected function display_table_header($form_name, $form_method, $remote_url, $state)
	{
		$ret = "<thead>";
		$ret .= "<tr>";

		$settings = $this->settings;
		$pagination_info = DataFormState::make_pagination_info($state, $settings, $this->table_name);

		// TODO: make this a parameter in DataTableColumn
		$sorting_formatter = new DefaultSortingFormatter();

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

			// TODO: Should figure out escaping of css classes.
			$css_classes = "column-" . str_replace(" ", "-", $column_key);
			if ($column->get_css_class() !== "") {
				$css_classes .= " " . $column->get_css_class();
			}
			if ($column->get_sortable()) {
				if ($remote_url) {
					$ret .= '<th class="' . htmlspecialchars($css_classes) . '">';
				}
				else
				{
					// let Javascript handle it
					// TODO: numeric and text sorting here
					$ret .= '<th class="' . htmlspecialchars($css_classes) . ' table-sortable:' . htmlspecialchars($column->get_sortable()) . ' table-sortable" title="Click to sort">';
				}
			}
			else
			{
				// no sorting
				$ret .= '<th class="' . htmlspecialchars($css_classes) . '">';
			}

			// If sortable, make header text a link which flips sorting
			$header_text = $column->get_display_header($form_name, $column_key, $state);

			/** @var DataTableColumn $column */
			if ($column->get_sortable() && $remote_url) {
				$sorting_state = $pagination_info->get_sorting_states();

				$column_sort_state = null;
				if (array_key_exists($column_key, $sorting_state)) {
					$column_sort_state = $sorting_state[$column_key];
				}

				$ret .= $sorting_formatter->format($form_name, $remote_url, $form_method,
					$this->table_name, $column_key,
					$state, $column_sort_state, $header_text);
			}
			else
			{
				$ret .= $header_text;
			}
			// display special header cell if specified

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
						$search_states = $pagination_info->get_search_states();
						if (array_key_exists($column_key, $search_states)) {
							$default_value = $search_states[$column_key];
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
	 * Note: most display functions just return a string, but this uses $writer because this may produce a huge amount of HTML
	 *
	 * @param $form_name string Name of form
	 * @param $form_method string GET or POST
	 * @param $remote_url string|bool The refresh url of the form, or false if local
	 * @param $state DataFormState State of form
	 * @param $writer IWriter Writer to output HTML to
	 * @return void
	 * @throws Exception
	 */
	protected function display_table_body($form_name, $form_method, $remote_url, $state, $writer)
	{
		$writer->write("<tbody>");

		// user can either have field names as keys for each row, or set them in $this->sql_field_names
		// and map them to indexes
		$indexes = array();
		$count = 0;
		foreach ($this->field_names as $field_name) {
			$indexes[$field_name] = $count;
			$count++;
		}

		if ($this->settings === null) {
			$settings = DataTableSettingsBuilder::create()->build();
		}
		else
		{
			$settings = $this->settings;
		}
		$pagination_info = DataFormState::make_pagination_info($state, $settings, $this->table_name);

		if ($pagination_info->get_limit() === 0) {
			$max_rows = $this->settings->get_total_rows();
		}
		else {
			$max_rows = $pagination_info->get_limit();
		}

		// end of header, start writing cells
		$row_count = 0;
		foreach ($this->rows as $row_id => $row) {
			if (!is_array($row)) {
				throw new Exception("Each row in rows expected to be an array");
			}
			if ($remote_url && !$settings->get_no_pagination() && $row_count >= $max_rows) {
				// The person creating the iterator must do the work of truncating it themselves
				throw new Exception("Exceeded permitted row count");
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

			$writer->write('<tr class="' . htmlspecialchars($row_class) . '">');


			// We are writing each column in order and only matching it up with data
			// if the data exists in $row
			foreach ($this->columns as $column) {
				$column_key = $column->get_column_key();

				// TODO: better escaping of css classes
				$css_classes = "column-" . str_replace(" ", "-", $column_key);
				if ($column->get_css_class() !== "") {
					$css_classes .= " " . $column->get_css_class();
				}

				$writer->write('<td class="' . htmlspecialchars($css_classes) . '">');
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
				$writer->write($column->get_display_data($form_name, $column_key, $cell, $row_id, $state));
				$writer->write("</td>");
			}

			$writer->write("</tr>");
			$row_count++;
		}
		$writer->write("</tbody>");
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

	/**
	 * Returns HTML to display table as widget.
	 *
	 * @param $form_name string Name of form
	 * @param $form_method string GET or POST
	 * @param $remote_url
	 * @param $state DataFormState State which may contain widget state
	 * @return string HTML
	 */
	public function display($form_name, $form_method, $remote_url, $state)
	{
		return $this->display_table($form_name, $form_method, $remote_url, $state);
	}

	/**
	 * Describes where widget will be rendered relative to textbox
	 * @return string
	 */
	public function get_placement()
	{
		return $this->placement;
	}
}
