<?php
require_once "../../../../../lib/main_lib.php";

require_once FILE_BASE_PATH . "/www/browser/lib/data_table/data_form.php";
require_once FILE_BASE_PATH . "/www/browser/lib/data_table/sql_builder.php";

$state = new DataFormState("browse_organisms", $_POST);

gfy_header("Browse organisms", "");

$data = $state->get_form_data();
if (array_key_exists("organism_id", $data)) {
	echo "Selected organism #" . $data["organism_id"];
}
else
{
	echo "No search selected";
}