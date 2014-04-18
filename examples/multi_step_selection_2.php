<?php
/**
 * Example of multiple step forms (step 2)
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
// this require contains information about the cities and zip codes in Massachusetts
require_once "data.php";

// comparators for zip codes

function compare_zip_column_asc($a, $b) {
	return $a["zip"] > $b["zip"];
}

function compare_zip_column_desc($a, $b) {
	return $a["zip"] < $b["zip"];
}
/**
 * Create DataForm of zip codes which match the cities in $prev_state
 *
 * @param DataFormState $prev_state Our state which was submitted from step 1
 * @param DataFormState $current_state Our state from refreshing the current page
 * @return DataForm
 */
function make_form($prev_state, $current_state) {
	$this_url = "multi_step_selection_2.php";
	$next_url = "multi_step_selection_3.php";

	// make two columns: a checkbox and the zip code. Zip code is sortable to show off this feature.
	$columns = array();
	$columns[] = DataTableColumnBuilder::create()
		->display_header_name("Select")
		->column_key("zip")
		->cell_formatter(new DataTableCheckboxCellFormatter())
		->build();
	$columns[] = DataTableColumnBuilder::create()
		->display_header_name("Zip code")
		->column_key("zip")
		->sortable(true)
		->build();

	// Simple continue button to submit to next page
	$buttons = array();
	$buttons[] = DataTableButtonBuilder::create()
		->text("Continue >>")
		->name("submit")
		->form_action($next_url)
		->behavior(new DataTableBehaviorSubmit())
		->build();

	// get list of selected cities
	$prev_form_data = $prev_state->get_form_data();
	if (array_key_exists("city", $prev_form_data)) {
		$selected_city = $prev_form_data["city"];
	}
	else
	{
		$selected_city = array();
	}

	// get zip codes which have cities
	$rows = array();
	foreach (get_data() as $obj) {
		if (in_array($obj["city"], $selected_city)) {
			$rows[$obj["zip"]] = array("zip" => $obj["zip"]);
		}
	}

	$settings = DataTableSettingsBuilder::create()
		->no_pagination()
		->build();

	// Note that $settings would have been created here with default values if it didn't already exist.
	$pagination_info = DataFormState::make_pagination_info($current_state, $settings);
	$current_sorting_state = $pagination_info->get_sorting_states();

	// sort zip codes
	if (isset($current_sorting_state["zip"])) {
		/** @var DataTableSortingState $column_sorting_state */
		$column_sorting_state = $current_sorting_state["zip"];
		if ($column_sorting_state->get_direction() == DataTableSortingState::sort_order_desc) {
			usort($rows, "compare_zip_column_desc");
		}
		elseif ($column_sorting_state->get_direction() == DataTableSortingState::sort_order_asc)
		{
			usort($rows, "compare_zip_column_asc");
		}
	}

	// mark each row with the rowid of the zip code
	$rows_with_rowid = array();
	foreach ($rows as $row) {
		$rows_with_rowid[$row["zip"]] = $row;
	}

	// create DataTable with the columns and rows we specified
	$table = DataTableBuilder::create()
		->columns($columns)
		->rows($rows_with_rowid)
		->widgets($buttons)
		->settings($settings)
		->build();
	$form = DataFormBuilder::create("select_zipcodes")
		->remote($this_url)
		->tables(array($table))
		->forwarded_state(array($prev_state))
		->build();
	return $form;
}

try {
	// Note on forwarded_state:
	// $_POST can either come from the previous form, or refreshes of the current form.
	// If $_POST comes from previous form, select_zipcodes is empty and select_cities comes from $_POST['select_cities']
	// If $_POST comes from current form as refresh, select_zipcodes has sorting data and
	//   select_cities comes from $_POST['select_zipcodes'][_state][_forwarded_state]['select_cities']


	// $current_state contains the sorting state for this form
	$current_state = new DataFormState("select_zipcodes", $_GET);
	// This comes either from $_POST or the forwarded_state within $current_state
	$prev_state = new DataFormState("select_cities", $_GET, $current_state);
	$prev_form_data = $prev_state->get_form_data();

	// Get selected cities so we can display a list of them
	if (array_key_exists("city", $prev_form_data)) {
		$selected_cities = $prev_form_data["city"];
	}
	else
	{
		$selected_cities = array();
	}

	if ($current_state->only_display_form()) {
		try {
			$form = make_form($prev_state, $current_state);
			echo $form->display_form($current_state);
		} catch (Exception $e) {
			echo json_encode(array("error" => $e->getMessage()));
		}
	}
	else
	{
		$form = make_form($prev_state, $current_state);
		gfy_header("Select zip codes", "");
		if ($selected_cities) {
			echo "You have selected: " . join(", ", $selected_cities) . "<br />";
		}
		else
		{
			echo "You have selected nothing!";
		}
		echo $form->display($current_state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e->getMessage() . "</pre>";
}