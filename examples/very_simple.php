<?php
require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";
require_once FILE_BASE_PATH . "/www/browser/lib/data_table/sql_builder.php";

/**
 * @param $state DataFormState
 * @returns DataForm
 */
function make_searches_table($state) {
	$sql = "SELECT search_name, search_date, run_id, scans_id, search_id  FROM searches";

	return create_data_form_from_database($sql, $state, "very_simple_submit.php", "search_id");
}

try
{
	$state = new DataFormState("browse_searches", $_POST);

	$form = make_searches_table($state);

	if ($state->only_display_form()) {
		echo $form->display_form($state);
	}
	else
	{
		gfy_header("Browse searches", "");
		echo $form->display($state);
	}
}
catch (Exception $e) {
	gfy_header("Browse searches", "");
	echo "<pre>" . $e . "</pre>";
}