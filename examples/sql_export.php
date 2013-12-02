<?php
require_once "sql_lib.php";

try {
	$state = new DataFormState("searches", $_POST);
	export_rows($state);
}
catch (Exception $e) {
	echo "<pre>" . $e . "</pre>";
}