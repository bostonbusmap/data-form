<?php
/**
 * Example for DataForm
 *
 * LICENSE: This source file and any compiled code are the property of its
 * respective author(s).  All Rights Reserved.  Unauthorized use is prohibited.
 *
 * @package    GFY Web Inteface
 * @author     George Schneeloch <george_schneeloch@hms.harvard.edu>
 * @copyright  2013 Above Authors and the President and Fellows of Harvard University
 */

require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";

/**
 * This example is meant to show how things would work without SQLBuilder, if we handled sorting and filtering
 * ourselves.
 */

/**
 * Simple formatter that turns everything in this column red.
 */
class RedFormatter implements IDataTableCellFormatter {
	public function format($form_name, $column_header, $column_data, $rowid, $state)
	{
		return "<span style='color: red;'>$column_data</span>";
	}
}

// comparators to describe how to compare a row
function compare_result_column_desc($a, $b) {
	return $a["result"] < $b["result"];
}

function compare_result_column_asc($a, $b) {
	return $a["result"] > $b["result"];
}

/**
 * @param DataFormState $state
 * @return DataForm
 * @throws Exception
 */
function make_form($state) {
	if (!($state instanceof DataFormState)) {
		throw new Exception("state must be instance of DataFormState");
	}
	$this_url = "live.php";

	// Make two columns: one to show numbers from 0 through 14 inclusive, and another column to show a modulo result
	$columns = array();
	$columns[] = DataTableColumnBuilder::create()
		->display_header_name("Numbers")
		->column_key("number")
		->build();
	$columns[] = DataTableColumnBuilder::create()
		->display_header_name("Result")
		->column_key("result")
		->sortable(true)
		->build();

	// get 'factor' value which is set using the select element defined below
	$multiplier = $state->find_item(array("factor"));
	if (is_null($multiplier)) {
		$multiplier = 4;
	}
	else
	{
		$multiplier = (int)$multiplier;
	}

	// Create some pretty simple data
	$rows = array();
	for ($i = 0; $i < 15; $i++) {
		$row = array();
		$row["number"] = $i;
		$row["result"] = ($i * $multiplier) % 7;

		$rows[] = $row;
	}

	// turn off pagination since we're not demonstrating it here
	$settings = DataTableSettingsBuilder::create()
		->no_pagination()
		->build();

	// Figure out sorting state given default $settings and user provided $state
	$pagination_info = DataFormState::make_pagination_info($state, $settings);

	$sorting_state = $pagination_info->get_sorting_states();

	// Sort the simple data using the comparators defined above
	if (isset($sorting_state["result"])) {
		/** @var DataTableSortingState $column_sorting_state */
		$column_sorting_state = $sorting_state["result"];
		if ($column_sorting_state->get_direction() === DataTableSortingState::sort_order_asc) {
			usort($rows, "compare_result_column_asc");
		}
		elseif ($column_sorting_state->get_direction() === DataTableSortingState::sort_order_desc) {
			usort($rows, "compare_result_column_desc");
		}
	}

	// Make a refresh button. This is not really necessary since refreshes happen whenever
	// sorting links are clicked.
	$buttons = array();
	$buttons[] = DataTableButtonBuilder::create()
		->text("(x * ?) % 7")
		->form_action($this_url)
		->behavior(new DataTableBehaviorRefresh())
		->build();

	// Make select element which will become 'multiplication[factor]' field

	// Note that '4' is selected by default, but this will be ignored
	// if there is a value defined for this element in DataFormState.
	$options = array();
	$options[] = new DataTableOption("3", "3");
	$options[] = new DataTableOption("4", "4", true);
	$options[] = new DataTableOption("5", "5");

	// Make the select element
	$buttons[] = DataTableOptionsBuilder::create()
		->options($options)
		->name("factor")
		->form_action($this_url)
		->behavior(new DataTableBehaviorRefresh())
		->build();

	// Create the DataTable, then the DataForm with the DataTable in it
	$table = DataTableBuilder::create()
		->columns($columns)
		->rows($rows)
		->widgets($buttons)
		->build();
	$form = DataFormBuilder::create($state->get_form_name())
		->tables(array($table))
		->remote($this_url)
		->build();
	return $form;
}

try {
	// $state contains our form state which contains sorting information and all our fields
	$state = new DataFormState("multiplication", $_GET);
	if ($state->only_display_form()) {
		try {
			$form = make_form($state);
			echo $form->display_form($state);
		}
		catch (Exception $e) {
			echo json_encode(array("error" => $e->getMessage()));
		}
	}
	else
	{
		$form = make_form($state);
		gfy_header("Simple table example", "");
		echo $form->display($state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e->getMessage() . "</pre>";
}