<?php
/**
 * Example of multiple step forms (step 3)
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

// comparators for zip code sorting
function compare_zip_column_desc($a, $b) {
	return $a["zip"] < $b["zip"];
}

function compare_zip_column_asc($a, $b) {
	return $a["zip"] > $b["zip"];
}

/**
 * This DataTable displays cities
 *
 * @param DataFormState $cities_state
 * @return DataTable
 */
function make_city_table($cities_state) {
	// Just one column that shows the city
	$columns = array();
	$columns[] = DataTableColumnBuilder::create()
		->display_header_name("Cities")
		->column_key("city")
		->build();

	$settings = DataTableSettingsBuilder::create()
		->no_pagination()
		->build();

	// Get list of cities from $cities_state
	$city_state_data = $cities_state->get_form_data();
	if (array_key_exists("city", $city_state_data)) {
		$selected_cities = $city_state_data["city"];
	}
	else
	{
		$selected_cities = array();
	}

	// Fill rows with city data
	$rows = array();
	foreach ($selected_cities as $city) {
		$rows[] = array("city" => $city);
	}

	// Make table for this information. Note the table name which is required when having two or more tables!
	$table = DataTableBuilder::create()
		->table_name("city")
		->columns($columns)
		->rows($rows)
		->settings($settings)
		->empty_message("No cities selected!")
		->build();
	return $table;
}

/**
 * This DataTable shows a sortable list of zip codes
 *
 * @param $zip_state DataFormState
 * @param $current_state DataFormState
 * @return DataTable
 */
function make_zip_table($zip_state, $current_state) {
	// One column with zip codes
	$columns = array();
	$columns[] = DataTableColumnBuilder::create()
		->display_header_name("Zip codes")
		->column_key("zip")
		->sortable(true)
		->build();

	// get zip codes from $zip_state
	$zip_state_data = $zip_state->get_form_data();
	if (array_key_exists("zip", $zip_state_data)) {
		$selected_zip_codes = $zip_state_data["zip"];
	}
	else
	{
		$selected_zip_codes = array();
	}

	// Fill rows with zip code data
	$rows = array();
	foreach ($selected_zip_codes as $zip) {
		$rows[] = array("zip" => $zip);
	}

	// Sort zip code data
	$table_name = "zip";

	$settings = DataTableSettingsBuilder::create()
		->no_pagination()
		->build();

	$pagination_info = DataFormState::make_pagination_info($current_state, $settings, $table_name);
	$sorting_state = $pagination_info->get_sorting_order();

	if (isset($sorting_state["zip"])) {
		if ($sorting_state["zip"] == DataFormState::sorting_state_desc) {
			usort($rows, "compare_zip_column_desc");
		}
		elseif ($sorting_state["zip"] == DataFormState::sorting_state_asc)
		{
			usort($rows, "compare_zip_column_asc");
		}
	}

	// Make DataTable of data.  Note the table name which is required when having two or more tables!
	$table = DataTableBuilder::create()
		->table_name($table_name)
		->columns($columns)
		->rows($rows)
		->empty_message("No zip codes selected!")
		->settings($settings)
		->build();
	return $table;
}

/**
 * @param $zip_state DataFormState
 * @param $city_state DataFormState
 * @param $current_state DataFormState
 * @return DataForm
 * @throws Exception
 */
function make_form($zip_state, $city_state, $current_state) {

	// create DataTables from DataFormState data
	$zip_table = make_zip_table($zip_state, $current_state);
	$city_table = make_city_table($city_state);
	// create a DataForm with both DataTables


	$form = DataFormBuilder::create("results")
		->tables(array($zip_table, $city_table))
		->remote("multi_step_selection_3.php")
		->forwarded_state(array($zip_state, $city_state))
		->build();
	return $form;
}

try {
	/* First page was cities, second page was zip codes, and third is current page
	 * $_POST is coming from either the previous page or a refresh of current page
	 * So $zip_code_state may come from $_POST['select_zipcodes'] or $_POST['results']['_state']['_forwarded_state']['select_zipcodes']
	 * And $city_state may come from either:
	 *  - $_POST['select_zipcodes']['_state']['_forwarded_state']['select_cities'] or
	 *  - $_POST['results']['_state']['_forwarded_state']['select_zipcodes']['_state']['_forwarded_state']['select_cities']
	 */


	$current_state = new DataFormState("results", $_GET);
	$zip_code_state = new DataFormState("select_zipcodes", $_GET, $current_state);
	$city_state = new DataFormState("select_cities", $_GET, $zip_code_state);

	// We can use StdoutWriter to output directly to standard output
	// so we don't create a huge intermediate string in memory
	$writer = new StdoutWriter();

	if ($current_state->only_display_form()) {
		try
		{
			$form = make_form($zip_code_state, $city_state, $current_state);
			$form->display_form_using_writer($writer, $current_state);
		}
		catch (Exception $e) {
			echo json_encode(array("error" => $e->getMessage()));
		}
	}
	else
	{
		$form = make_form($zip_code_state, $city_state, $current_state);
		gfy_header("Show results", "");
		$form->display_using_writer($writer, $current_state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e->getMessage() . "</pre>";
}