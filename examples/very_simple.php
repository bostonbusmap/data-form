<?php
require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";
require_once FILE_BASE_PATH . "/www/browser/lib/data_table/sql_builder.php";

/**
 * @param $state DataFormState
 * @returns DataForm
 */
function make_organisms_table($state) {
	$sql = "SELECT *  FROM organisms";

	return create_data_form_from_database($sql, $state, "very_simple_submit.php", "organism_id");
}

try
{
	$state = new DataFormState("browse_organisms", $_POST);

	$form = make_organisms_table($state);

	if ($state->only_display_form()) {
		echo $form->display_form($state);
	}
	else
	{
		gfy_header("Browse organisms", "");
		echo $form->display($state);
	}
}
catch (Exception $e) {
	gfy_header("Browse organisms", "");
	echo "<pre>" . $e . "</pre>";
}