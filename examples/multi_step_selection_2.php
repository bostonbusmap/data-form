<?php

require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";
require_once "data.php";


function compare_zip_column_asc($a, $b) {
	return $a["zip"] < $b["zip"];
}

function compare_zip_column_desc($a, $b) {
	return $a["zip"] > $b["zip"];
}
/**
 * @param DataFormState $prev_state
 * @param DataFormState $current_state
 * @return DataForm
 */
function make_form($prev_state, $current_state) {
	$this_url = HTTP_BASE_PATH . "/browser/lib/data_table/examples/multi_step_selection_2.php";
	$next_url = HTTP_BASE_PATH . "/browser/lib/data_table/examples/multi_step_selection_3.php";

	$columns = array();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Select")->column_key("zip")->
		cell_formatter(new DataTableCheckboxCellFormatter())->build();
	$columns[] = DataTableColumnBuilder::create()->display_header_name("Zip code")->column_key("zip")->sortable(true)->build();

	$buttons = array();
	$buttons[] = DataTableButtonBuilder::create()->text("Continue >>")->name("submit")->form_action($next_url)->
		behavior(new DataTableBehaviorSubmit())->build();

	$prev_form_data = $prev_state->get_form_data();
	if (array_key_exists("city", $prev_form_data)) {
		$selected_city = $prev_form_data["city"];
	}
	else
	{
		$selected_city = array();
	}

	$rows = array();
	foreach (get_data() as $obj) {
		if (in_array($obj["city"], $selected_city)) {
			$rows[$obj["zip"]] = array("zip" => $obj["zip"]);
		}
	}

	$current_sorting_state = $current_state->get_sorting_state("zip");
	if ($current_sorting_state == DataFormState::sorting_state_desc) {
		usort($rows, "compare_zip_column_desc");
	}
	else
	{
		usort($rows, "compare_zip_column_asc");
	}

	$table = DataTableBuilder::create()->columns($columns)->rows($rows)->widgets($buttons)->remote($this_url)->build();
	$form = DataFormBuilder::create("select_zipcodes")->tables(array($table))->
		method("GET")->
		forwarded_state(array($prev_state, $current_state))->build();
	return $form;
}

try {
	$current_state = new DataFormState("select_zipcodes", $_GET);
	$prev_state = new DataFormState("select_cities", $_GET, $current_state);
	$prev_form_data = $prev_state->get_form_data();

	if (array_key_exists("city", $prev_form_data)) {
		$selected_cities = $prev_form_data["city"];
	}
	else
	{
		$selected_cities = array();
	}

	$form = make_form($prev_state, $current_state);
	if ($current_state->only_display_form()) {
		echo $form->display_form($current_state);
	}
	else
	{
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
	echo "<pre>" . $e . "</pre>";
}