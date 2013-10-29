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
 * @param DataFormState $cities_state
 * @param DataFormState $zip_codes_state
 * @param DataFormState $current_state
 * @return DataForm
 */
function make_form($cities_state, $zip_codes_state, $current_state) {
	$this_url = HTTP_BASE_PATH . "/browser/lib/data_table/examples/multi_step_selection_3.php";

	$columns = array();
	$columns[] = new DataTableColumn("Cities", "city");
	$columns[] = new DataTableColumn("Zip code", "zip", null, null, true);

	$city_state_data = $cities_state->get_form_data();
	$selected_cities = $city_state_data["city"];
	$zip_data = $zip_codes_state->get_form_data();
	$selected_zip_codes = $zip_data["zip"];

	$rows = array();
	foreach (get_data() as $obj) {
		if (in_array($obj["city"], $selected_cities) && in_array($obj["zip"], $selected_zip_codes)) {
			$rows[] = array("zip" => $obj["zip"], "city" => $obj["city"]);
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

	$table = DataTableBuilder::create()->columns($columns)->rows($rows)->remote($this_url)->build();
	$form = DataFormBuilder::create("results")->tables(array($table))->build();
	return $form;
}

try {
	$current_state = new DataFormState("results", $_POST);
	$zip_code_state = new DataFormState("select_zipcodes", $_POST, $current_state);
	$city_state = new DataFormState("select_cities", $_POST, $zip_code_state);

	$form = make_form($city_state, $zip_code_state, $current_state);
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