<?php
require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";
require_once "data.php";

try {
	$current_state = new DataFormState("select_3", $_POST);

	gfy_header("Show results", "");
	$numbers = $current_state->find_item(array("number"));
	if ($numbers) {
		foreach ($numbers as $number) {
			echo "Selected item " . $number . "<br />";
		}
	}
	else
	{
		echo "No even numbers selected";
	}
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}