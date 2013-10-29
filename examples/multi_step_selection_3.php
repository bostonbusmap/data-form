<?php
require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";
require_once "data.php";


function compare_zip_column_desc($a, $b) {
	return $a["zip"] < $b["zip"];
}

function compare_zip_column_asc($a, $b) {
	return $a["zip"] > $b["zip"];
}

/**
 * @param DataFormState $cities_state
 * @param DataFormState $current_state
 * @return DataTable
 */
function make_city_table($cities_state, $current_state) {
	$this_url = HTTP_BASE_PATH . "/browser/lib/data_table/examples/multi_step_selection_3.php";

	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Cities")->column_key("city")->build();

	$city_state_data = $cities_state->get_form_data();
	if (array_key_exists("city", $city_state_data)) {
		$selected_cities = $city_state_data["city"];
	}
	else
	{
		$selected_cities = array();
	}

	$rows = array();
	foreach ($selected_cities as $city) {
		$rows[] = array("city" => $city);
	}

	$table = DataTableBuilder::create()->table_name("city")->columns($columns)->
		rows($rows)->remote($this_url)->empty_message("No cities selected!")->build();
	return $table;
}

/**
 * @param $zip_state DataFormState
 * @param $current_state DataFormState
 * @return DataTable
 */
function make_zip_table($zip_state, $current_state) {
	$this_url = HTTP_BASE_PATH . "/browser/lib/data_table/examples/multi_step_selection_3.php";

	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Zip codes")->column_key("zip")->sortable(true)->build();

	$zip_state_data = $zip_state->get_form_data();
	if (array_key_exists("zip", $zip_state_data)) {
		$selected_zip_codes = $zip_state_data["zip"];
	}
	else
	{
		$selected_zip_codes = array();
	}

	$rows = array();
	foreach ($selected_zip_codes as $zip) {
		$rows[] = array("zip" => $zip);
	}

	$current_sorting_state = $current_state->get_sorting_state("zip");
	if ($current_sorting_state == DataFormState::sorting_state_desc) {
		usort($rows, "compare_zip_column_desc");
	}
	else
	{
		usort($rows, "compare_zip_column_asc");
	}

	$table = DataTableBuilder::create()->table_name("zip")->columns($columns)->
		rows($rows)->remote($this_url)->empty_message("No zip codes selected!")->build();
	return $table;
}

try {
	$current_state = new DataFormState("results", $_GET);
	$zip_code_state = new DataFormState("select_zipcodes", $_GET, $current_state);
	$city_state = new DataFormState("select_cities", $_GET, $zip_code_state);

	$zip_table = make_zip_table($zip_code_state, $current_state);
	$city_table = make_city_table($city_state, $current_state);
	$form = DataFormBuilder::create("results")->tables(array($zip_table, $city_table))->
		method("GET")->
		forwarded_state(array($current_state, $zip_code_state))->build();

	if ($current_state->only_display_form()) {
		echo $form->display_form($current_state);
	}
	else
	{
		gfy_header("Show results", "");
		echo $form->display($current_state);
	}
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}