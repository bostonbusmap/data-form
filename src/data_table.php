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
require_once "data_table_column.php";
require_once "data_table_header_formatter.php";
require_once "data_table_link.php";
require_once "data_table_options.php";
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
	/** @var \DataTableButton[]  */
	private $buttons;
	/** @var \DataTableColumn[]  */
	private $columns;
	/** @var \string[]  */
	private $sql_field_names;
	/** @var array  */
	private $rows;

	/** @var string|bool Either false or a URL to send pagination, sorting, or searching requests to */
	private $remote;

	/**
	 * @param DataTableBuilder $builder
	 * @throws Exception
	 */
	public function __construct($builder) {
		$this->buttons = $builder->get_buttons();
		$this->columns = $builder->get_columns();
		$this->sql_field_names = $builder->get_sql_field_names();
		$this->rows = $builder->get_rows();
		$this->remote = $builder->get_remote();
	}

	/**
	 * Returns HTML for table. This is useful if sending it via ajax to populate a div
	 *
	 * @param DataFormState $state
	 * @param string $form_name
	 * @return string HTML
	 * @throws Exception
	 */
	public function display_table($state, $form_name) {
		$ret = "";

		$indexes = array();
		$count = 0;
		foreach ($this->sql_field_names as $field_name) {
			$indexes[$field_name] = $count;
			$count++;
		}

		foreach ($this->buttons as $button) {
			if ($button->get_placement() == DataTableButton::placement_top) {
				$ret .= $button->display($form_name, $state);
			}
		}

		if ($this->is_sortable()) {
			$ret .= "<table class='table-autosort table-stripeclass:shadedbg table-altstripeclass:shadedbg'>";
		}
		else
		{
			$ret .= "<table class='table-stripeclass:shadedbg table-altstripeclass:shadedbg'>";
		}
		$ret .= "<thead>";
		$ret .= "<tr class='standard-table-header'>";
		foreach ($this->columns as $column_key => $column) {
			if ($column->get_sortable()) {
				if ($this->remote) {
					$ret .= "<th class='column_" . $column_key . "'>";
					$old_sorting_state = $state->get_sorting_state($column_key);
					if ($old_sorting_state == DataFormState::sorting_state_asc) {
						$ret .= "&uarr; ";
					}
					elseif ($old_sorting_state == DataFormState::sorting_state_desc) {
						$ret .= "&darr; ";
					}
				}
				else
				{
					$ret .= "<th class='column_" . $column_key . " table-sortable:" . $column->get_sortable() . " table-sortable' title='Click to sort'>";
				}
			}
			else
			{
				$ret .= "<th class='column_" . $column_key . "'>";
			}

			/** @var DataTableColumn $column */
			if ($column->get_sortable() && $this->remote) {
				if ($state->get_sorting_state($column_key)) {
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
				$sort_string = "&" . $form_name . "[" . DataFormState::sorting_state_key . "][" . $column_key . "]=" . $new_sorting_state;

				$onclick = new DataTableBehaviorRefresh($sort_string);
				$ret .= "<a onclick='$onclick'>";
			}
			$ret .= $column->get_display_header($form_name, $column_key);
			if ($column->get_sortable() && $this->remote) {
				$ret .= "</a>";
			}
			$ret .= "</th>";
		}
		$ret .= "</tr>";

		if ($this->is_searchable()) {
			$ret .= "<tr class='standard-table-header'>";
			foreach ($this->columns as $column_key => $column) {
				$ret .= "<th>";
				if ($column->get_searchable()) {
					if (is_string($column->get_searchable())) {
						$ret .= "<strong>" . $column->get_searchable() . "</strong>";
					}
					else
					{
						$ret .= "<input size='8' onkeyup='Table.filter(this, this)' />";
					}
				}
				$ret .= "</th>";
			}
			$ret .= "</tr>";
		}
		$ret .= "</thead>";
		$ret .= "<tbody>";
		$row_count = 0;
		foreach ($this->rows as $row_id => $row) {
			$row_id = (string)$row_id;

			$shaded = "";
			if ($row_count % 2 == 0) {
				$shaded = "unshaded";
			}
			$ret .= "<tr class='shadedbg $shaded'>";


			foreach ($this->columns as $column_key => $column) {
				$ret .= "<td class='column_$column_key'>";
				/** @var DataTableColumn $column */
				if (array_key_exists($column_key, $row)) {
					$cell = $row[$column_key];
				}
				elseif (array_key_exists($column_key, $indexes)) {
					$index = $indexes[$column_key];
					if ($index >= count($row)) {
						throw new Exception("Tried to get index $index of row of size " . count($row));
					}
					if (array_key_exists($index, $row)) {
						$cell = $row[$index];
					}
					else
					{
						$cell = null;
					}
				}
				else
				{
					// a column where there is no data is a common case, for instance
					// the row selection checkbox
					$cell = null;
				}
				$ret .= $column->get_display_data($form_name, $column_key, $cell, $row_id, $state);
				$ret .= "</td>";
			}

			echo "</tr>";
			$row_count++;
		}
		$ret .= "</tbody>";
		$ret .= "</table>";
		foreach ($this->buttons as $button) {
			if ($button->get_placement() == DataTableButton::placement_bottom) {
				$ret .= $button->display($form_name, $state);
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
}
